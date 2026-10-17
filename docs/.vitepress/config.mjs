import { defineConfig } from 'vitepress';

const guideSidebar = [
	{ text: 'Getting started', link: '/guide/getting-started' },
	{
		text: 'Usage',
		link: '/guide/usage',
		items: [
			{ text: 'has()', link: '/guide/usage#has' },
			{ text: 'get()', link: '/guide/usage#get' },
			{ text: 'set()', link: '/guide/usage#set' },
			{ text: 'make()', link: '/guide/usage#make' },
		],
	},
	{ text: 'App config options', link: '/guide/app-config-options' },
	{ text: 'WordPress example', link: '/guide/full-wordpress' },
	{ text: 'Namespace prefix', link: '/guide/namespaces' },
];

const resourcesSidebar = [
	{ text: 'Container comparison', link: '/reference/comparison' },
	{ text: 'App config comparison', link: '/reference/app-config-comparison' },
	{ text: 'Limitations', link: '/reference/limitations' },
	{ text: 'Benchmarks', link: '/reference/benchmarks' },
	{ text: 'FAQ and support', link: '/reference/faq' },
];

export default defineConfig( {
	lang: 'en-US',
	title: 'LiteWire DI',
	description: 'A tiny single-file autowiring DI container for PHP and WordPress.',
	base: '/litewire-di/',
	cleanUrls: true,
	markdown: {
		config( md ) {
			for( const type of [ 'info', 'tip', 'warning', 'danger' ] ) {
				const rule = `container_${type}_open`;
				const render = md.renderer.rules[ rule ];

				md.renderer.rules[ rule ] = ( tokens, index, options, env, renderer ) => {
					const token = tokens[ index ];
					if( token.info.trim() === type ) {
						return `<div class="${ type } custom-block no-title"${ renderer.renderAttrs( token ) }>\n`;
					}

					return render( tokens, index, options, env, renderer );
				};
			}
		},
	},
	head: [
		[ 'link', { rel: 'icon', href: '/litewire-di/logo.svg', type: 'image/svg+xml' } ],
	],
	themeConfig: {
		logo: '/logo.svg',
		outline: {
			level: [ 2, 4 ],
		},
		nav: [
			{ text: 'Guide', link: '/guide/getting-started' },
			{ text: 'Benchmarks', link: '/reference/benchmarks' },
			{ text: 'Packagist', link: 'https://packagist.org/packages/doiftrue/litewire-di' },
		],
		sidebar: [
			{ text: 'Guides', items: guideSidebar },
			{ text: 'More', items: resourcesSidebar },
		],
		socialLinks: [
			{ icon: 'github', link: 'https://github.com/doiftrue/litewire-di' },
		],
		search: {
			provider: 'local',
		},
		footer: {
			message: 'Released under the MIT License.',
			copyright: 'Copyright © Timur Kamaev',
		},
	},
} );
