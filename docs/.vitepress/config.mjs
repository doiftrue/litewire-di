import { defineConfig } from 'vitepress';

const guideSidebar = [
	{ text: 'Getting started', link: '/guide/getting-started' },
	{ text: 'Using the container', link: '/guide/using-the-container' },
	{ text: 'Configuration and factories', link: '/guide/configuration-and-factories' },
	{ text: 'WordPress integration', link: '/guide/wordpress' },
];

const referenceSidebar = [
	{ text: 'API reference', link: '/reference/api' },
	{ text: 'Limitations and troubleshooting', link: '/reference/limitations' },
	{ text: 'Benchmarks', link: '/reference/benchmarks' },
	{ text: 'FAQ and support', link: '/reference/faq' },
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
		nav: [
			{ text: 'Guide', link: '/guide/getting-started' },
			{ text: 'Reference', link: '/reference/api' },
			{ text: 'Benchmarks', link: '/reference/benchmarks' },
			{ text: 'Packagist', link: 'https://packagist.org/packages/doiftrue/litewire-di' },
		],
		sidebar: [
			{ text: 'Guides', items: guideSidebar },
			{ text: 'Reference', items: referenceSidebar },
		],
		socialLinks: [
			{ icon: 'github', link: 'https://github.com/doiftrue/litewire-di' },
		],
		editLink: {
			pattern: 'https://github.com/doiftrue/litewire-di/edit/main/docs/:path',
			text: 'Edit this page on GitHub',
		},
		search: {
			provider: 'local',
		},
		footer: {
			message: 'Released under the MIT License.',
			copyright: 'Copyright © Timur Kamaev',
		},
	},
} );
