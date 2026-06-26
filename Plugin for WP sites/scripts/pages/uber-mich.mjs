// Спека страницы «Über mich» для движка scripts/import-page.mjs.
// Эквивалент import-uber-mich.mjs, но как данные: общий код вынесен в движок.
// Запуск: npm run page -- uber-mich        (или: npm run page -- uber-mich --dry)

export const title = 'Über mich';
export const slug = 'ueber-mich';

// Figma CDN URLs действительны 7 дней с момента get_design_context.
export const media = {
	bioPortrait:    { slug: 'rosenberger-bio-portrait-v2',    ext: 'webp' },
	founderPhoto:   { slug: 'rosenberger-founder-photo',      remote: 'https://www.figma.com/api/mcp/asset/952ad1e2-2a49-4f29-87c8-2d2ca910be6c', ext: 'png' },
	quoteCover:     { slug: 'rosenberger-quote-cover',        remote: 'https://www.figma.com/api/mcp/asset/ac5e6cac-485b-433f-8a34-5cec385657c0', ext: 'png' },
	ctaBg:          { slug: 'rosenberger-consultation-bg' },
	badge:          { slug: 'rosenberger-google-rating' },
	iconHouse:      { slug: 'rosenberger-card-icon-house' },
	iconEvaluation: { slug: 'rosenberger-card-icon-evaluation' },
	iconValet:      { slug: 'rosenberger-card-icon-valet' },
};

export const blocks = ( m ) => [
	{ name: 'bio-hero', attrs: {
		label:      'Über mich',
		name:       'Alex Rosenberger',
		jobTitle:   'Immobilienmakler in Vorarlberg',
		bio:        'Ich kenne Vorarlbergs Immobilienmarkt aus zwei Perspektiven: als Makler, der seit 2021 hier tätig ist, und als jemand, der selbst auf der Käuferseite gesessen hat und erlebt hat, wie es nicht sein sollte.',
		nameCredit: 'Alexander\nRosenberger',
		imageId:    m.bioPortrait.id,
		imageUrl:   m.bioPortrait.url,
	} },

	{ name: 'trust-bar', attrs: {
		badgeId:  m.badge.id,
		badgeUrl: m.badge.url,
		items: [ 'Persönlich von mir betreut', 'Schnelle Rückmeldung', 'Vor Ort in ganz Vorarlberg' ],
	} },

	{ name: 'founder-story', attrs: {
		heading: 'Warum ich Makler<br>geworden bin',
		lead:    'Bevor ich 2021 ROSENBERGER Immobilien gegründet habe, war ich selbst auf der Suche nach einer Immobilie in Vorarlberg.',
		body:    'Was ich dabei erlebt habe, hat mich mehr geformt als jede Ausbildung danach. Ich habe Makler erlebt, die im Erstgespräch Preise nannten, die der Markt nie bestätigt hat. Ich habe auf Rückrufe gewartet, die nicht kamen. Ich habe Exposés gesehen, die mit dem tatsächlichen Objekt kaum noch etwas zu tun hatten.',
		quote:   'Das war keine Ausnahme.\nDas war die Regel.',
	} },

	{ name: 'quote-cover', attrs: {
		text:     'Ihr Immobilienmakler\nin Vorarlberg',
		imageId:  m.quoteCover.id,
		imageUrl: m.quoteCover.url,
	} },

	{ name: 'founder-bio', attrs: {
		heading:    'Was Eigentümer brauchen, ist kein Immobilienmakler, sondern Vertrauen.',
		paragraphs: [
			'Irgendwann habe ich aufgehört, darauf zu warten, dass jemand diese Arbeit anders macht. Ich habe begonnen, selbst darüber nachzudenken, was ein Makler einem Eigentümer schuldet und was er einem Käufer schuldet.',
			'2021 habe ich ROSENBERGER Immobilien gegründet. Nicht um ein weiteres Maklerbüro zu eröffnen. Sondern weil ich einen konkreten Grund hatte: Ich wusste, wie es sich anfühlt, wenn ein Makler seine Arbeit nicht macht. Und ich wusste, wie es sich anfühlen sollte.',
			'Diese Erfahrung sitzt in jeder Bewertung, die ich abgebe, und in jedem Gespräch, das ich führe. Sie erinnert mich daran, was für Sie auf dem Spiel steht, wenn ich Ihre Immobilie vermarkte.',
		],
		imageId:  m.founderPhoto.id,
		imageUrl: m.founderPhoto.url,
	} },

	{ name: 'value-cards', attrs: {
		cards: [
			{ title: 'Ehrliche <br>Bewertung',         text: 'Ich sage Ihnen, was der Markt für Ihre Immobilie zahlt. Nicht was ich sagen müsste, um den Auftrag zu bekommen.',        iconId: m.iconHouse.id,      iconUrl: m.iconHouse.url      },
			{ title: 'Direkte <br>Erreichbarkeit',      text: 'Sie erreichen mich auf meiner persönlichen Mobilnummer. Ich melde mich, ohne dass Sie nachfragen müssen.',               iconId: m.iconEvaluation.id, iconUrl: m.iconEvaluation.url },
			{ title: 'Rund eine Stunde Aufwand für Sie', text: 'Das Erstgespräch. Den Rest übernehme ich. Von der Bewertung bis zur Ummeldung nach der Übergabe.',                    iconId: m.iconValet.id,      iconUrl: m.iconValet.url      },
		],
	} },

	{ name: 'promise-list', attrs: {
		heading: 'Was Sie<br>von mir erwarten dürfen',
		items: [
			{ number: '01', title: 'Schriftliche Wertmitteilung<br>nach der Bewertung',       text: 'Nach unserem Gespräch erhalten Sie von mir eine schriftliche Einschätzung mit Bewertungsbasis und Vergleichsdaten. Keine mündliche Zahl, die sich später nicht mehr nachvollziehen lässt.' },
			{ number: '02', title: 'Status-Updates ohne Nachfragen',                        text: 'Sie erfahren regelmäßig, wie viele Anfragen eingegangen sind, welche Besichtigungen stattgefunden haben und wo die Vermarktung steht. Ich melde mich bei Ihnen, nicht umgekehrt.' },
			{ number: '03', title: 'Geprüfte Interessenten,<br>kein Besichtigungstourismus',  text: 'Ich filtere vor jeder Besichtigung, wer ernsthaftes Interesse hat und wer nicht. Ihr Zuhause zeige ich nur Menschen, die kaufen können und wollen.' },
			{ number: '04', title: 'Ein Ansprechpartner von Anfang bis Ende',               text: 'Sie sprechen vom ersten Gespräch bis zur Schlüsselübergabe ausschließlich mit mir. Kein wechselndes Personal, keine Weiterleitung an einen Kollegen.' },
		],
	} },

	{ name: 'consultation-cta', attrs: {
		heading:       '',
		headingItalic: 'Lernen Sie mich kennen',
		text:          'Ich bin in Feldkirch ansässig und für Eigentümer in ganz Vorarlberg tätig. Ein erstes Gespräch kostet nichts und verpflichtet Sie zu nichts. Sie erfahren, was Ihre Immobilie wert ist und wie ich den Verkauf für Sie abwickle.',
		buttonText:    'Kostenlos beraten lassen',
		buttonUrl:     '/kontakt/',
		backgroundId:  m.ctaBg.id,
		backgroundUrl: m.ctaBg.url,
	} },
];
