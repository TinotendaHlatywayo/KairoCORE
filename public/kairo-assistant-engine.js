const STOP_WORDS = new Set(['a','an','the','is','are','was','were','be','been','being','to','of','in','on','at','for','with','and','or','but','if','then','so','as','by','from','up','out','about','into','over','after','before','under','again','once','here','there','when','where','why','how','what','which','who','whom','whose','all','any','both','each','few','more','most','other','some','such','no','nor','not','only','own','same','than','too','very','can','will','just','should','now','i','me','my','we','our','you','your','it','its','they','them','their','this','that','these','those','do','does','did','doing','have','has','had','having','get','got','please','help','tell','show','need','want']);

const KEYWORDS = {
  import: ['import','excel','csv','template','bulk','upload','spreadsheet'],
  export: ['export','download','print','pdf','excel','csv','report'],
  create: ['new','add','create','register','enrol','enroll','generate','make'],
  edit: ['edit','update','change','amend','modify'],
  remove: ['delete','remove','archive','cancel'],
  view: ['view','open','see','find','inspect','show'],
  payment: ['fee','fees','invoice','invoices','payment','payments','receipt','balance','billing','tuition'],
  student: ['student','students','pupil','pupils','learner','learners'],
  staff: ['staff','employee','employees','teacher','teachers','hr','payroll'],
  exam: ['exam','exams','assessment','assessments','mark','marks','grade','grades','result','results'],
  connect: ['connect','link','relate','relationship','integrate','impact','affect','sync'],
  website: ['website','site','cms','page','pages','content'],
  library: ['library','book','books','eresource','circulation'],
  hostel: ['hostel','boarding','accommodation','room','rooms','bed'],
  health: ['health','clinic','medical','sick'],
};

const INTENTS = {
  how: /\b(how|steps?|workflow|guide|walk|start|begin|use)\b/i,
  current: /\b(this page|current page|here|what am i|where am i|help me here|get started)\b/i,
  import: /\b(import|excel|csv|template|bulk|upload|spreadsheet)\b/i,
  export: /\b(export|download|print|pdf|excel|csv|report)\b/i,
  create: /\b(new|add|create|register|enrol|enroll|generate|make)\b/i,
  edit: /\b(edit|update|change|amend|modify)\b/i,
  remove: /\b(delete|remove|archive)\b/i,
  connect: /\b(connect|link|relate|relationship|integrate|impact|affect|sync)\b/i,
};

function normalize(text) {
  return String(text || '')
    .toLowerCase()
    .replace(/[’‘]/g, "'")
    .replace(/[^a-z0-9\s'-]/g, ' ')
    .replace(/\s+/g, ' ')
    .trim()
    .slice(0, 240);
}

function stem(token) {
  if (token.length <= 3) return token;
  if (token.endsWith('ies')) return token.slice(0, -3) + 'y';
  if (token.endsWith('ing') && token.length > 5) return token.slice(0, -3);
  if (token.endsWith('ed') && token.length > 5) return token.slice(0, -2);
  if (token.endsWith('s') && !token.endsWith('ss')) return token.slice(0, -1);
  return token;
}

function tokens(text) {
  const source = normalize(text);
  return source.split(' ').filter((word) => word && !STOP_WORDS.has(word)).map(stem);
}

const SUBJECT_GROUPS = ['payment', 'student', 'staff', 'exam', 'website', 'library', 'hostel', 'health'];

function terms(query) {
  const set = new Set(tokens(query));
  for (const token of [...set]) {
    for (const name of SUBJECT_GROUPS) {
      const group = KEYWORDS[name] || [];
      if (group.some((word) => stem(word) === token)) {
        group.forEach((word) => set.add(stem(word)));
      }
    }
  }
  return [...set];
}

function cleanStep(step) {
  return String(step || '')
    .replace(/\*\*/g, '')
    .replace(/^\s*\d+\.\s*(?:Step\s*\d+\s*-\s*)?/i, '')
    .trim();
}

function cleanSteps(steps) {
  return (steps || []).map(cleanStep).filter(Boolean);
}

function workflowText(item) {
  return [
    item.title,
    item.label,
    item.module,
    item.group,
    item.summary,
    ...cleanSteps(item.workflow),
    ...Object.entries(item.details || {}).map(([label, text]) => `${label} ${text}`),
    String(item.url || '').replace(/^\/+/, '').replace(/\//g, ' '),
  ].join(' ');
}

function faqText(item) {
  return [
    item.question,
    ...(item.tags || []),
    ...(item.keywords || []),
    ...(item.phrasings || []).slice(0, 4),
    item.answer,
  ].join(' ');
}

function idf(corpus) {
  const counts = new Map();
  corpus.forEach((item) => {
    const set = new Set(tokens(item.text));
    set.forEach((token) => counts.set(token, (counts.get(token) || 0) + 1));
  });
  const n = corpus.length || 1;
  const weights = {};
  counts.forEach((count, token) => {
    weights[token] = Math.log((n + 1) / (count + 0.5)) + 0.6;
  });
  return weights;
}

function scoreItem(query, item, queryTerms, weights) {
  const q = normalize(query);
  const title = normalize(item.title || item.question || '');
  const focus = normalize((item.label || '') + ' ' + (item.title || item.question || ''));
  const text = normalize(item.text);
  const titleTokens = new Set(tokens(title));
  const focusTokens = new Set(tokens(focus));
  const textTokens = new Set(tokens(text));

  let score = 0;
  let specific = false;

  if (q && (title === q || focus === q)) {
    score += 80;
    specific = true;
  }
  if (q.length > 5 && title.includes(q)) {
    score += 45;
    specific = true;
  }
  if (q.length > 5 && focus.includes(q)) {
    score += 25;
    specific = true;
  }

  for (const token of queryTerms) {
    const weight = weights[token] || 1;
    if (titleTokens.has(token)) {
      score += 14 * weight;
      specific = true;
    } else if (focusTokens.has(token)) {
      score += 10 * weight;
      specific = true;
    } else if (textTokens.has(token)) {
      score += 4 * weight;
    }
  }

  const words = tokens(q);
  for (let i = 0; i < words.length - 1; i++) {
    const bigram = `${words[i]} ${words[i + 1]}`;
    if (title.includes(bigram)) score += 10;
    else if (text.includes(bigram)) score += 5;
  }

  if (INTENTS.current.test(q) && item.current) {
    score += 140;
    specific = true;
  }
  if (/\b(first|start|begin)\b/i.test(q) && item.current) {
    score += 20;
    specific = true;
  }

  const intentHits = Object.entries(INTENTS)
    .filter(([name, pattern]) => name !== 'how' && name !== 'current' && pattern.test(q))
    .map(([name]) => name);

  const faqFocus = normalize([item.question || '', ...(item.tags || []), ...(item.keywords || [])].join(' '));

  for (const intent of intentHits) {
    const group = KEYWORDS[intent] || [];
    if (item.kind === 'faq') {
      if (group.some((word) => faqFocus.includes(word))) {
        score += 16;
        specific = true;
      }
      continue;
    }
    if (group.some((word) => text.includes(word))) score += 16;
    if (group.some((word) => title.includes(word))) score += 8;
  }

  if (item.kind === 'workflow') {
    const label = normalize(item.label || item.title || '');
    if (label && q.includes(label)) {
      score += 35;
      specific = true;
    }

    if (INTENTS.how.test(q) || intentHits.length) {
      const steps = normalize((item.workflow || []).join(' '));
      const actionWords = intentHits.flatMap((intent) => KEYWORDS[intent] || []);
      score += 10;
      if (actionWords.some((word) => steps.includes(word))) {
        score += 28;
        specific = true;
      }
    }
  }

  if (INTENTS.connect.test(q) && /cross module|relationship|linked|connect/i.test(text)) score += 18;
  if (item.current && /page|here|this/i.test(q)) {
    score += 12;
    specific = true;
  }

  return { score, specific };
}

function buildCorpus(context) {
  const corpus = [];

  (context.workflows || []).forEach((item) => {
    corpus.push({
      kind: 'workflow',
      id: item.id || item.url,
      title: item.title || item.label || 'Workspace guide',
      label: item.label || item.title || 'Workspace guide',
      module: item.module || '',
      group: item.group || '',
      summary: item.summary || '',
      workflow: cleanSteps(item.workflow),
      details: item.details || {},
      url: item.url || '',
      current: Boolean(item.current),
      text: workflowText(item),
    });
  });

  (context.kb || []).forEach((item) => {
    corpus.push({
      kind: 'faq',
      id: item.id,
      title: item.question || item.id,
      question: item.question || '',
      answer: item.answer || '',
      related: item.related || [],
      source: item.source || null,
      category: item.category || '',
      text: faqText(item),
    });
  });

  return corpus;
}

function relevantSteps(steps, queryTerms) {
  const scored = steps.map((step, index) => {
    const set = new Set(tokens(step));
    const score = queryTerms.reduce((total, token) => total + (set.has(token) ? 2 : 0), 0);
    return { step, index, score };
  });

  if (!scored.some((item) => item.score > 0)) return steps;

  return [...scored].sort((a, b) => b.score - a.score).map((item) => item.step);
}

function faqParts(answer) {
  const lines = String(answer || '')
    .split(/\n+/)
    .map((line) => line.trim())
    .filter(Boolean);

  const steps = [];
  const notes = [];

  lines.forEach((line) => {
    const step = line.replace(/^(?:\d+[.)]|•|-)\s*/, '').trim();
    if (/^(?:\d+[.)]|•|-)\s*/.test(line) && step) steps.push(step);
    else notes.push(line);
  });

  return { steps, notes };
}

function introFor(query, item, context) {
  const q = normalize(query);
  const title = item.title || context.currentTitle || 'this page';

  if (INTENTS.current.test(q)) return `Here is the step-by-step workflow for ${title}.`;
  if (INTENTS.import.test(q)) return `To import records in ${title}, follow this workflow. The template and validation steps are included.`;
  if (INTENTS.export.test(q)) return `To export from ${title}, use the steps below. The export options are part of this page's workflow.`;
  if (INTENTS.create.test(q)) return `To add or create a record in ${title}, start with the first step below.`;
  if (INTENTS.edit.test(q)) return `To update records in ${title}, follow the workflow below.`;
  if (INTENTS.remove.test(q)) return `To remove records safely in ${title}, follow the workflow below.`;
  if (INTENTS.connect.test(q)) return `${title} connects to other Kairo CORE modules. Here is the full workflow and the linked notes.`;
  if (INTENTS.how.test(q)) return `Here is the step-by-step workflow for ${title}.`;
  return `Here's what Kairo CORE says about ${title}.`;
}

function followUpsFor(item, context) {
  const base = (context.suggestions || []).filter((question) => !/this page|here/i.test(question));
  const related = [];

  (item.related || []).forEach((id) => {
    const entry = (context.kb || []).find((candidate) => candidate.id === id);
    if (entry?.question) related.push(entry.question);
  });

  const current = context.currentTitle
    ? [`How do I use ${context.currentTitle}?`, `Walk me through ${context.currentTitle}`]
    : [];

  return [...new Set([...related, ...base, ...current])].slice(0, 5);
}

export function assistantAnswer(question, context = {}) {
  const q = normalize(question);
  const corpus = buildCorpus(context);
  const weights = idf(corpus);
  const queryTerms = terms(q);

  if (!q || q.length < 2) {
    return {
      kind: 'fallback',
      title: 'Ask me about Kairo CORE',
      intro: 'Ask a question about a page, a workflow, or a module — for example, “How do I import students?”',
      steps: [],
      notes: [],
      links: [],
      followUps: context.suggestions || [],
      source: null,
      confidence: 'low',
    };
  }

  if (!corpus.length || !queryTerms.length) {
    return {
      kind: 'fallback',
      title: 'I focus on Kairo CORE',
      intro: 'I don’t have a reliable answer for that. I can guide you through the open page or point you to the full documentation.',
      steps: [],
      notes: [],
      links: [],
      followUps: context.suggestions || [],
      source: null,
      confidence: 'low',
    };
  }

  const scored = corpus
    .map((item) => ({ item, ...scoreItem(q, item, queryTerms, weights) }))
    .sort((a, b) => b.score - a.score);

  const top = scored[0];
  const meaningful = queryTerms.some((token) => token.length > 2);

  if (!top || top.score < 9 || !top.specific || !meaningful) {
    return {
      kind: 'fallback',
      title: 'Let me point you in the right direction',
      intro: 'I’m tuned to Kairo CORE pages and guides, so I don’t want to guess on that. I can walk you through the open page or send you to the full documentation.',
      steps: [],
      notes: context.currentTitle ? [`Right now you are on ${context.currentTitle}.`] : [],
      links: [],
      followUps: context.suggestions || [],
      source: null,
      confidence: 'low',
    };
  }

  const item = top.item;
  const confidence = top.score >= 42 ? 'high' : top.score >= 20 ? 'medium' : 'low';

  if (item.kind === 'workflow') {
    const links = [];
    if (item.url && item.url !== '/workspace' && item.url.startsWith('/')) {
      links.push({ label: `Open ${item.label}`, url: item.url });
    }
    (context.pages || []).forEach((page) => {
      if (links.length >= 3) return;
      const url = String(page.url || '');
      if (!url.startsWith('/') || url === '/workspace') return;
      const text = normalize(`${page.label} ${page.group} ${url}`);
      if (queryTerms.some((token) => text.includes(token))) {
        links.push({ label: page.label, url });
      }
    });

    return {
      kind: 'workflow',
      title: item.title,
      intro: introFor(q, item, context),
      steps: relevantSteps(item.workflow, queryTerms),
      notes: Object.entries(item.details || {}).map(([label, text]) => ({ label, text: String(text) })),
      links,
      followUps: followUpsFor(item, context),
      source: { title: item.title, url: item.url || null },
      confidence,
    };
  }

  const parts = faqParts(item.answer);
  const links = [];

  (item.related || []).slice(0, 2).forEach((id) => {
    const entry = (context.kb || []).find((candidate) => candidate.id === id);
    if (entry?.question) links.push({ label: entry.question, question: entry.question });
  });

  if (item.source?.url && /^https?:\/\//.test(item.source.url)) {
    links.push({ label: item.source.title || 'Source guide', url: item.source.url });
  }

  return {
    kind: 'faq',
    title: item.title,
    intro: 'From the Kairo CORE guide:',
    steps: parts.steps,
    notes: parts.notes.map((text) => ({ text })),
    links,
    followUps: followUpsFor(item, context),
    source: item.source || null,
    confidence,
  };
}
