import { defineConfig } from 'vitepress';

const guideSidebar = [
	{ text: 'Getting started', link: '/guide/getting-started' },
	{ text: 'Using the container', link: '/guide/using-the-container' },
	{ text: 'Configuring services', link: '/guide/configuring-services' },
];

const docSidebar = [
	{ text: 'Container guide', link: '/documentation/full-documentation' },
	{ text: 'Configuration cookbook', link: '/documentation/full-configuration' },
	{ text: 'WordPress example', link: '/documentation/full-wordpress' },
];

const moreSidebar = [
	{ text: 'Container comparison', link: '/more/comparison' },
	{ text: 'Limitations', link: '/more/limitations' },
	{ text: 'Benchmarks', link: '/more/benchmarks' },
	{ text: 'FAQ and support', link: '/more/faq' },
];

export default defineConfig( {
	lang: 'en-US',
	title: 'LiteWire DI',
	description: 'A tiny single-file autowiring DI container for PHP and WordPress.',
	base: '/litewire-di/',
	cleanUrls: true,
	head: [
		[ 'link', { rel: 'icon', href: '/litewire-di/logo.svg', type: 'image/svg+xml' } ],
	],
	themeConfig: {
		logo: '/logo.svg',
		outline: {
			level: [ 2, 3 ],
		},
		nav: [
			{ text: 'Guide', link: '/guide/getting-started' },
			{ text: 'Documentation', link: '/documentation/full-documentation' },
			{ text: 'Benchmarks', link: '/more/benchmarks' },
			{ text: 'Packagist', link: 'https://packagist.org/packages/doiftrue/litewire-di' },
		],
		sidebar: [
			{ text: 'Guides', items: guideSidebar },
			{ text: 'Documentation', items: docSidebar },
			{ text: 'More', items: moreSidebar },
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
