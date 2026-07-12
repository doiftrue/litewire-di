
const examples = {
	get: {
		title: 'Resolve a shared service',
		code: `<span class="k">use</span> <span class="t">Kama\\LiteWireDI\\Container</span>;

<span class="v">$container</span> = <span class="k">new</span> <span class="t">Container</span>();

<span class="v">$service</span> = <span class="v">$container</span>-><span class="f">get</span>( <span class="t">Service</span>::class );
<span class="v">$same</span> = <span class="v">$container</span>-><span class="f">get</span>( <span class="t">Service</span>::class );

var_dump( <span class="v">$service</span> === <span class="v">$same</span> ); <span class="c">// true</span>`,
		raw: `use Kama\\LiteWireDI\\Container;

$container = new Container();

$service = $container->get( Service::class );
$same = $container->get( Service::class );

var_dump( $service === $same ); // true`
	},
	set: {
		title: 'Bind an interface or factory',
		code: `<span class="v">$container</span>-><span class="f">set</span>(
	<span class="t">Logger_Interface</span>::class,
	<span class="t">File_Logger</span>::class
);

<span class="v">$container</span>-><span class="f">set</span>(
	<span class="t">Client</span>::class,
	<span class="k">static function</span> ( <span class="t">Logger_Interface</span> <span class="v">$logger</span> ) {
		<span class="k">return new</span> <span class="t">Client</span>( <span class="s">'https://example.com'</span>, <span class="v">$logger</span> );
	}
);`,
		raw: `$container->set(
	Logger_Interface::class,
	File_Logger::class
);

$container->set(
	Client::class,
	static function ( Logger_Interface $logger ) {
		return new Client( 'https://example.com', $logger );
	}
);`
	},
	make: {
		title: 'Create with runtime values',
		code: `<span class="k">final class</span> <span class="t">Mailer</span> {
	<span class="k">public function</span> <span class="f">__construct</span>(
		<span class="k">private</span> <span class="t">Logger</span> <span class="v">$logger</span>,
		<span class="k">private</span> <span class="t">string</span> <span class="v">$from</span>
	) {}
}

<span class="v">$mailer</span> = <span class="v">$container</span>-><span class="f">make</span>( <span class="t">Mailer</span>::class, [
	<span class="s">'from'</span> => <span class="s">'admin@example.com'</span>,
] );`,
		raw: `final class Mailer {
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
		code: `<span class="c">// Registered, resolved, or autowireable.</span>
<span class="v">$container</span>-><span class="f">has</span>( <span class="t">Service</span>::class ); <span class="c">// true</span>

<span class="c">// Unknown class or interface.</span>
<span class="v">$container</span>-><span class="f">has</span>( <span class="s">'Unknown'</span> ); <span class="c">// false</span>

<span class="c">// For a class, has() validates its full
// constructor dependency graph.</span>`,
		raw: `// Registered, resolved, or autowireable.
$container->has( Service::class ); // true

// Unknown class or interface.
$container->has( 'Unknown' ); // false

// For a class, has() validates its full
// constructor dependency graph.`
	}
};

const tabs = Array.from( document.querySelectorAll( '.api-tab' ) );
const code = document.querySelector( '#example-code' );
const title = document.querySelector( '#example-title' );
const panel = document.querySelector( '#code-panel' );
const copyButton = document.querySelector( '#copy-button' );
let activeExample = 'get';

function selectExample( name ) {
	activeExample = name;
	code.innerHTML = examples[ name ].code;
	title.textContent = examples[ name ].title;
	panel.setAttribute( 'aria-labelledby', `tab-${ name }` );

	tabs.forEach( ( tab ) => {
		const selected = tab.dataset.example === name;
		tab.setAttribute( 'aria-selected', selected ? 'true' : 'false' );
		tab.tabIndex = selected ? 0 : -1;
	} );
}

tabs.forEach( ( tab, index ) => {
	tab.addEventListener( 'click', () => selectExample( tab.dataset.example ) );
	tab.addEventListener( 'keydown', ( event ) => {
		if ( ! [ 'ArrowLeft', 'ArrowRight' ].includes( event.key ) ) {
			return;
		}

		event.preventDefault();
		const direction = event.key === 'ArrowRight' ? 1 : -1;
		const next = ( index + direction + tabs.length ) % tabs.length;
		tabs[ next ].focus();
		selectExample( tabs[ next ].dataset.example );
	} );
} );

copyButton.addEventListener( 'click', async () => {
	try {
		await navigator.clipboard.writeText( examples[ activeExample ].raw );
		copyButton.textContent = 'Copied';
		setTimeout( () => copyButton.textContent = 'Copy code', 1500 );
	} catch ( error ) {
		copyButton.textContent = 'Select code';
	}
} );

const header = document.querySelector( '#site-header' );
window.addEventListener( 'scroll', () => {
	header.classList.toggle( 'is-scrolled', window.scrollY > 8 );
}, { passive: true } );

const reducedMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
const reveals = document.querySelectorAll( '.reveal' );

if ( reducedMotion || ! ( 'IntersectionObserver' in window ) ) {
	reveals.forEach( ( item ) => item.classList.add( 'is-visible' ) );
} else {
	const observer = new IntersectionObserver( ( entries ) => {
		entries.forEach( ( entry ) => {
			if ( entry.isIntersecting ) {
				entry.target.classList.add( 'is-visible' );
				observer.unobserve( entry.target );
			}
		} );
	}, { threshold: 0.12 } );

	reveals.forEach( ( item ) => observer.observe( item ) );
}

selectExample( activeExample );
