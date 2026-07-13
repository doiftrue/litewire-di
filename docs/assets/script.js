const examples = {
	get: {
		title: 'Resolve a shared service',
		description: 'Returns and stores one shared object.',
		href: '#get',
		code: `use Kama\\LiteWireDI\\Container;

$container = new Container();

$service = $container->get( Service::class );
$same = $container->get( Service::class );

var_dump( $service === $same ); // true`
	},
	set: {
		title: 'Bind an interface or factory',
		description: 'Registers an object, class name, or closure factory.',
		href: '#set',
		code: `$container->set(
	Logger::class,
	FileLogger::class
);

$container->set(
	Client::class,
	static function ( Logger $logger ) {
		return new Client( $logger );
	}
);`
	},
	make: {
		title: 'Create with runtime values',
		description: 'Creates a fresh root object and accepts named parameters.',
		href: '#make',
		code: `final class Mailer {
	public function __construct(
		private Logger $logger,
		private string $from
	) {}
}

$mailer = $container->make( Mailer::class, [
	'from' => 'admin@example.com',
] );`
	},
	has: {
		title: 'Check the complete graph',
		description: 'Checks registered, resolved, and autowireable services.',
		href: '#has',
		code: `// Registered, resolved, or autowireable.
$container->has( Service::class ); // true

// Unknown class or interface.
$container->has( 'Unknown' ); // false

// For a class, has() validates the complete
// constructor dependency graph.`
	}
};

const root = document.documentElement;
const themeButton = document.querySelector( '.theme-toggle' );

function currentTheme() {
	return root.dataset.theme || ( matchMedia( '(prefers-color-scheme: dark)' ).matches ? 'dark' : 'light' );
}

function updateThemeButton() {
	if ( ! themeButton ) return;
	const next = currentTheme() === 'dark' ? 'light' : 'dark';
	themeButton.setAttribute( 'aria-label', `Switch to ${ next } theme` );
}

themeButton?.addEventListener( 'click', () => {
	const next = currentTheme() === 'dark' ? 'light' : 'dark';
	root.dataset.theme = next;
	try { localStorage.setItem( 'litewire-theme', next ); } catch ( error ) {}
	updateThemeButton();
} );
updateThemeButton();

const tabs = Array.from( document.querySelectorAll( '.api-tab' ) );
const exampleCode = document.querySelector( '#example-code' );
const exampleTitle = document.querySelector( '#example-title' );
const exampleDescription = document.querySelector( '#example-description' );
const exampleLink = document.querySelector( '#example-link' );
const examplePanel = document.querySelector( '#code-panel' );
const exampleCopy = document.querySelector( '#copy-button' );
let activeExample = 'get';

function selectExample( name ) {
	if ( ! examples[ name ] || ! exampleCode ) return;
	activeExample = name;
	exampleCode.textContent = examples[ name ].code;
	if ( window.Prism ) Prism.highlightElement( exampleCode );
	exampleTitle.textContent = examples[ name ].title;
	exampleDescription.textContent = examples[ name ].description;
	exampleLink.href = examples[ name ].href;
	examplePanel.setAttribute( 'aria-labelledby', `tab-${ name }` );

	tabs.forEach( ( tab ) => {
		const selected = tab.dataset.example === name;
		tab.setAttribute( 'aria-selected', selected ? 'true' : 'false' );
		tab.tabIndex = selected ? 0 : -1;
	} );
}

tabs.forEach( ( tab, index ) => {
	tab.addEventListener( 'click', () => selectExample( tab.dataset.example ) );
	tab.addEventListener( 'keydown', ( event ) => {
		if ( ! [ 'ArrowLeft', 'ArrowRight', 'Home', 'End' ].includes( event.key ) ) return;
		event.preventDefault();
		let next = event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1 : index + ( event.key === 'ArrowRight' ? 1 : -1 );
		next = ( next + tabs.length ) % tabs.length;
		tabs[ next ].focus();
		selectExample( tabs[ next ].dataset.example );
	} );
} );

async function copyText( text, button, defaultLabel ) {
	try {
		await navigator.clipboard.writeText( text );
		button.textContent = 'Copied';
		setTimeout( () => { button.textContent = defaultLabel; }, 1500 );
	} catch ( error ) {
		button.textContent = 'Select code';
	}
}

exampleCopy?.addEventListener( 'click', () => copyText( exampleCode.textContent, exampleCopy, 'Copy code' ) );
selectExample( activeExample );

document.querySelectorAll( '.copy-code' ).forEach( ( button ) => {
	button.addEventListener( 'click', () => {
		const code = button.closest( '.code-block' )?.querySelector( 'code' );
		if ( code ) copyText( code.textContent, button, 'Copy' );
	} );
} );

const header = document.querySelector( '#site-header' );
function updateHeader() { header?.classList.toggle( 'is-scrolled', window.scrollY > 8 ); }
window.addEventListener( 'scroll', updateHeader, { passive: true } );
updateHeader();

const menuButton = document.querySelector( '.menu-button' );
const primaryLinks = document.querySelector( '#primary-links' );
menuButton?.addEventListener( 'click', () => {
	const open = primaryLinks.classList.toggle( 'is-open' );
	menuButton.setAttribute( 'aria-expanded', String( open ) );
} );
primaryLinks?.addEventListener( 'click', ( event ) => {
	if ( event.target.closest( 'a' ) ) {
		primaryLinks.classList.remove( 'is-open' );
		menuButton?.setAttribute( 'aria-expanded', 'false' );
	}
} );

const pageToc = document.querySelector( '#page-toc-links' );
const documentTabs = Array.from( document.querySelectorAll( '.document-tabs a' ) );
const documentationSidebar = document.querySelector( '#documentation-sidebar' );
const sidebarToggle = document.querySelector( '.sidebar-toggle' );
const sidebarClose = document.querySelector( '.sidebar-close' );

function setSidebarOpen( open ) {
	documentationSidebar?.classList.toggle( 'is-open', open );
	sidebarToggle?.setAttribute( 'aria-expanded', String( open ) );
}

sidebarToggle?.addEventListener( 'click', () => setSidebarOpen( ! documentationSidebar?.classList.contains( 'is-open' ) ) );
sidebarClose?.addEventListener( 'click', () => setSidebarOpen( false ) );
documentationSidebar?.addEventListener( 'click', ( event ) => {
	if ( event.target.closest( 'a' ) ) setSidebarOpen( false );
} );
document.addEventListener( 'keydown', ( event ) => {
	if ( event.key === 'Escape' ) setSidebarOpen( false );
} );

function renderPageToc( source ) {
	if ( ! pageToc || ! source ) return;
	pageToc.replaceChildren();
	const headings = Array.from( source.querySelectorAll( 'h2[id], h3[id]' ) );
	headings.forEach( ( heading ) => {
		const link = document.createElement( 'a' );
		link.href = `#${ heading.id }`;
		link.textContent = heading.firstChild?.textContent?.trim() || heading.textContent.trim();
		link.dataset.level = heading.tagName.toLowerCase();
		pageToc.append( link );
	} );
}

const documentSources = Array.from( document.querySelectorAll( '#docs-content .doc-source' ) );
const hashTarget = location.hash ? document.getElementById( decodeURIComponent( location.hash.slice( 1 ) ) ) : null;
const initialSource = hashTarget?.closest( '.doc-source' ) || documentSources[ 0 ];
renderPageToc( initialSource );
documentTabs.forEach( ( tab ) => tab.classList.toggle( 'is-active', tab.dataset.document === initialSource?.dataset.docSource ) );

if ( 'IntersectionObserver' in window ) {
	const sourceObserver = new IntersectionObserver( ( entries ) => {
		const current = entries
			.filter( ( entry ) => entry.isIntersecting )
			.sort( ( first, second ) => first.boundingClientRect.top - second.boundingClientRect.top )[ 0 ];
		if ( current ) {
			renderPageToc( current.target );
			documentTabs.forEach( ( tab ) => tab.classList.toggle( 'is-active', tab.dataset.document === current.target.dataset.docSource ) );
		}
	}, { rootMargin: '-12% 0px -72% 0px', threshold: 0 } );
	documentSources.forEach( ( source ) => sourceObserver.observe( source ) );
}

const observedSections = Array.from( document.querySelectorAll( '#docs-content [id]' ) );

if ( 'IntersectionObserver' in window ) {
	const sectionObserver = new IntersectionObserver( ( entries ) => {
		const visible = entries.filter( ( entry ) => entry.isIntersecting ).sort( ( a, b ) => a.boundingClientRect.top - b.boundingClientRect.top )[ 0 ];
		if ( ! visible ) return;
		document.querySelectorAll( '.page-toc-sections a' ).forEach( ( link ) => link.classList.toggle( 'is-active', link.hash === `#${ visible.target.id }` ) );
	}, { rootMargin: '-18% 0px -72% 0px', threshold: 0 } );
	observedSections.forEach( ( section ) => sectionObserver.observe( section ) );
}
