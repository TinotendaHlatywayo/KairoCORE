export const STOP_WORDS = new Set(['a','an','the','is','are','was','were','be','been','being','to','of','in','on','at','for','with','and','or','but','if','then','so','as','by','from','up','out','about','into','over','after','before','between','under','again','further','once','here','there','when','where','why','how','all','any','both','each','few','more','most','other','some','such','no','nor','not','only','own','same','than','too','very','can','will','just','should','now','i','me','my','we','our','you','your','it','its','they','them','their','this','that','these','those','do','does','did','doing','have','has','had','having','get','got','gets']);

export const SYNONYMS = {
  fee: ['fees','tuition','invoice','invoices','billing','payment','payments','pay'],
  fees: ['fee','tuition','invoice','invoices','billing','payment','payments','pay'],
  tuition: ['fee','fees','invoice','billing','payment'],
  invoice: ['fees','fee','billing','payment','receipt'],
  invoices: ['fees','fee','billing','payment','receipt'],
  billing: ['fees','fee','invoice','payment'],
  payment: ['fees','fee','invoice','receipt','pay'],
  payments: ['fees','fee','invoice','receipt','pay'],
  pay: ['fees','fee','payment','invoice'],
  pupil: ['pupils','student','students','learner','learners'],
  pupils: ['pupil','student','students','learner','learners'],
  student: ['students','pupil','pupils','learner','learners'],
  students: ['student','pupil','pupils','learner','learners'],
  learner: ['learners','student','students','pupil','pupils'],
  learners: ['learner','student','students','pupil','pupils'],
  teacher: ['teachers','staff','employee','employees'],
  teachers: ['teacher','staff','employee','employees'],
  staff: ['teacher','teachers','employee','employees'],
  employee: ['employees','staff','teacher','teachers'],
  employees: ['employee','staff','teacher','teachers'],
  'report card': ['report','reports','results','grades','marks'],
  report: ['reports','report card','results','grades','marks'],
  reports: ['report','report card','results','grades','marks'],
  results: ['report','reports','grades','marks','result'],
  grades: ['grade','report','reports','results','marks'],
  marks: ['mark','grade','grades','results','report'],
  enrol: ['enroll','enrolment','enrollment','register','registration'],
  enroll: ['enrol','enrolment','enrollment','register','registration'],
  enrolment: ['enroll','enrol','enrollment','register'],
  enrollment: ['enrol','enroll','enrolment','register'],
  register: ['registration','enrol','enroll','signup','sign'],
  registration: ['register','enrol','enroll','signup'],
  help: ['support','guide','docs','documentation'],
  docs: ['documentation','help','guide'],
  documentation: ['docs','help','guide'],
  website: ['site','web','cms','page','pages'],
  site: ['website','web','cms','page'],
};

const CATEGORY_HINTS = {
  fees: 'finance', fee: 'finance', tuition: 'finance', invoice: 'finance', payment: 'finance', payments: 'finance', receipt: 'finance', balance: 'finance', arrears: 'finance',
  student: 'students', students: 'students', pupil: 'students', pupils: 'students', learner: 'students', learners: 'students', enrol: 'students', enroll: 'students',
  staff: 'hr', teacher: 'hr', teachers: 'hr', employee: 'hr', employees: 'hr', payroll: 'hr', leave: 'hr', attendance: 'hr',
  report: 'exams', reports: 'exams', marks: 'exams', assessment: 'exams', assessments: 'exams', grade: 'exams', grades: 'exams', results: 'exams',
  library: 'library', book: 'library', books: 'library',
  boarding: 'boarding', hostel: 'boarding', room: 'boarding', bed: 'boarding',
  health: 'health', clinic: 'health', medical: 'health',
  announcement: 'communication', announcements: 'communication', notice: 'communication',
  homework: 'lms', lesson: 'lms', lessons: 'lms',
  website: 'website', cms: 'website', template: 'website', page: 'website',
  inventory: 'inventory', stock: 'inventory', procurement: 'inventory', asset: 'inventory', assets: 'inventory', supplier: 'inventory',
  permission: 'administration', role: 'administration', roles: 'administration', settings: 'administration', subscription: 'administration',
  troubleshooting: 'troubleshooting', login: 'troubleshooting', error: 'troubleshooting', issue: 'troubleshooting',
  admissions: 'admissions', admission: 'admissions', application: 'admissions', apply: 'admissions',
  academics: 'academics', subject: 'academics', subjects: 'academics', stream: 'academics', classroom: 'academics',
};

const CATEGORY_PRIMARY = {
  finance: 'fee-structures-and-invoicing',
  students: 'student-records',
  student: 'student-records',
  hr: 'employees-and-directory',
  communication: 'announcements',
  admissions: 'online-applications',
  exams: 'assessments-and-marks',
  reports: 'reporting-and-analytics',
  boarding: 'boarding-and-welfare',
  health: 'health-records-and-clinic',
  academics: 'academic-structure',
  inventory: 'stock-and-procurement',
  library: 'library-catalogue-and-circulation',
  lms: 'homework-and-lessons',
  website: 'school-website-cms',
  administration: 'staff-permissions',
  troubleshooting: 'common-issues',
};

const SMALL_TALK = {
  en: [
    { match: /^(hi|hello|hey|hiya|howdy|good (morning|afternoon|evening)|greetings)\b/i, reply: 'Hello! I’m the Kairo assistant. Ask me anything about running your school with Kairo CORE.' },
    { match: /^(thanks|thank you|thx|cheers|appreciate)\b/i, reply: 'You’re welcome. If you need more help, message the team on WhatsApp.' },
    { match: /^(bye|goodbye|see you|ciao)\b/i, reply: 'Goodbye. Have a great day running your school.' },
    { match: /(are you (a )?(bot|ai|human|person|real)|are you (an )?ai|is this (a )?bot|who are you)/i, reply: 'I’m an automated assistant that answers from Kairo CORE’s guides. A person is one WhatsApp message away if you need human help.' },
  ],
  fr: [
    { match: /^(bonjour|salut|coucou|bonsoir|bjr)\b/i, reply: "Bonjour ! Je suis l’assistant Kairo. Posez-moi vos questions sur la gestion de votre école avec Kairo CORE." },
    { match: /^(merci|thanks)\b/i, reply: "Avec plaisir. Si vous avez besoin d’aide supplémentaire, contactez l’équipe sur WhatsApp." },
    { match: /^(au revoir|bye|a bientot|à bientôt)\b/i, reply: "Au revoir. Bonne journée avec votre école." },
    { match: /(etes[- ]vous (un )?(bot|robot|ia|humain)|es[- ]tu (un )?(bot|robot|ia)|qui etes[- ]vous|qui es[- ]tu)/i, reply: "Je suis l’assistant Kairo, une aide automatisée. Une personne est disponible sur WhatsApp si vous avez besoin d’aide humaine." },
  ],
  es: [
    { match: /^(hola|buenos dias|buenos días|buenas tardes|buenas noches)\b/i, reply: "¡Hola! Soy el asistente de Kairo. Pregúntame lo que necesites sobre Kairo CORE." },
    { match: /^(gracias|muchas gracias)\b/i, reply: "De nada. Si necesitas más ayuda, escribe al equipo por WhatsApp." },
    { match: /^(adios|adiós|hasta luego|chao)\b/i, reply: "Adiós. Que tengas un buen día." },
    { match: /(eres (un )?(bot|robot|ia|humano)|quien eres|quién eres)/i, reply: "Soy el asistente de Kairo, una ayuda automatizada. Una persona está disponible por WhatsApp si necesitas ayuda humana." },
  ],
  pt: [
    { match: /^(olá|ola|bom dia|boa tarde|boa noite)\b/i, reply: "Olá! Sou o assistente Kairo. Pergunte-me sobre a gestão da sua escola com o Kairo CORE." },
    { match: /^(obrigado|obrigada)\b/i, reply: "De nada. Se precisar de mais ajuda, contacte a equipa no WhatsApp." },
    { match: /^(adeus|tchau|ate logo|até logo)\b/i, reply: "Adeus. Tenha um ótimo dia." },
    { match: /(es (um )?(bot|robô|ia|humano)|quem es|quem és)/i, reply: "Sou o assistente Kairo, uma ajuda automatizada. Uma pessoa está disponível no WhatsApp se precisar de ajuda humana." },
  ],
  sw: [
    { match: /^(habari|hujambo|salamu|shikamoo|mambo|jambo)\b/i, reply: "Habari! Mimi ni msaidizi wa Kairo. Niulize chochote kuhusu Kairo CORE." },
    { match: /^(asante|shukran)\b/i, reply: "Karibu. Ukihitaji msaada zaidi, wasiliana na timu kupitia WhatsApp." },
    { match: /^(kwa heri|baadaye)\b/i, reply: "Kwa heri. Uwe na siku njema." },
    { match: /(wewe ni (roboti|bot|ai|binadamu)|nani wewe)/i, reply: "Mimi ni msaidizi wa Kairo, msaada wa kiotomatiki. Mtu yupo WhatsApp ikiwa unahitaji msaada wa kibinadamu." },
  ],
  sn: [
    { match: /^(mhoro|makadii|maswera sei|mangwanani|masikati|manheru)\b/i, reply: "Mhoro! Ndiri mubatsiri weKairo. Ndibvunzei nezveKairo CORE." },
    { match: /^(mazvita|tatenda|ndatenda)\b/i, reply: "Mauya. Kana muchida rumwe rubatsiro, bata chikwata paWhatsApp." },
    { match: /^(sara zvakanaka|toonana)\b/i, reply: "Sara zvakanaka. Ivai nezuva rakanaka." },
    { match: /(uri (bot|roboti|ai|munhu)|ndiwe ani)/i, reply: "Ndiri mubatsiri weKairo, rubatsiro rweotomatiki. Munhu aripo paWhatsApp kana muchida rubatsiro." },
  ],
};

const ABUSE = ['fuck','shit','bitch','asshole','idiot','stupid'];

export function normalize(text) {
  return String(text || '')
    .toLowerCase()
    .replace(/[’‘]/g, "'")
    .replace(/[^a-z0-9\s'-]/g, ' ')
    .replace(/\s+/g, ' ')
    .trim()
    .slice(0, 200);
}

export function stem(token) {
  if (token.length <= 3) return token;
  if (token.endsWith('ies')) return token.slice(0, -3) + 'y';
  if (token.endsWith('s') && !token.endsWith('ss')) return token.slice(0, -1);
  if (token.endsWith('ing')) return token.slice(0, -3);
  if (token.endsWith('ed')) return token.slice(0, -2);
  return token;
}

export function levenshtein(a, b) {
  if (a === b) return 0;
  if (!a.length) return b.length;
  if (!b.length) return a.length;
  let prev = Array.from({ length: b.length + 1 }, (_, i) => i);
  for (let i = 1; i <= a.length; i++) {
    let cur = [i];
    for (let j = 1; j <= b.length; j++) {
      const cost = a[i - 1] === b[j - 1] ? 0 : 1;
      cur[j] = Math.min(prev[j] + 1, cur[j - 1] + 1, prev[j - 1] + cost);
    }
    prev = cur;
  }
  return prev[b.length];
}

export function tokensOf(text) {
  return normalize(text).split(' ').filter(Boolean);
}

function expandedTokens(text) {
  const toks = tokensOf(text).map(stem);
  const out = new Set(toks);
  for (const t of toks) {
    if (SYNONYMS[t]) for (const s of SYNONYMS[t]) out.add(stem(s));
  }
  return [...out];
}

function isGibberish(text) {
  const t = normalize(text);
  if (!t) return true;
  if (t.length > 200) return true;
  if (ABUSE.some(w => t.includes(w))) return true;
  const toks = t.split(' ').filter(Boolean);
  if (toks.length === 1 && toks[0].length > 12 && !/[aeiou]/.test(toks[0])) return true;
  if (toks.every(tok => tok.length >= 6 && !/[aeiou]/.test(tok))) return true;
  return false;
}

export function idfWeights(kb) {
  const df = {};
  const n = kb.length || 1;
  for (const e of kb) {
    const set = new Set(expandedTokens((e.keywords || []).join(' ') + ' ' + (e.question || '') + ' ' + (e.answer || '') + ' ' + (e.tags || []).join(' ')));
    for (const t of set) df[t] = (df[t] || 0) + 1;
  }
  const w = {};
  for (const t in df) w[t] = Math.log(n / df[t]) + 1;
  return w;
}

export function scoreEntry(query, entry, weights, previousTopic) {
  const q = normalize(query);
  const qTokens = tokensOf(q).map(stem).filter(t => !STOP_WORDS.has(t));
  const qExpanded = new Set(expandedTokens(q));
  const entryText = normalize((entry.question || '') + ' ' + (entry.answer || '') + ' ' + (entry.tags || []).join(' ') + ' ' + (entry.keywords || []).join(' '));
  const entryTokens = new Set(expandedTokens(entryText));
  const phrasings = (entry.phrasings || []).map(normalize);

  let score = 0;
  // exact / near phrasing
  for (const p of phrasings) {
    if (p === q) { score += 100; break; }
    const d = levenshtein(p, q);
    if (d <= 2 && q.length >= 8) { score += 70 - d * 10; break; }
  }
  // keyword hits
  for (const t of qTokens) {
    const tw = weights[t] || 1;
    if (entryTokens.has(t)) score += tw * 4 + 8;
    if (normalize(entry.question || '').includes(t)) score += 4;
    // synonym expansion
    if (SYNONYMS[t]) {
      for (const s of SYNONYMS[t]) {
        if (entryTokens.has(stem(s))) score += tw * 2;
      }
    }
    // typo tolerance per token
    for (const et of entryTokens) {
      if (et.length >= 5 && t.length >= 5) {
        const d = levenshtein(t, et);
        const threshold = t.length >= 9 ? 2 : 1;
        if (d > 0 && d <= threshold) score += (weights[t] || 1) * 1.5;
      }
    }
  }
  // bigrams
  const qBigrams = [];
  for (let i = 0; i < qTokens.length - 1; i++) qBigrams.push(qTokens[i] + ' ' + qTokens[i+1]);
  for (const bg of qBigrams) {
    if (entryText.includes(bg)) score += 6;
  }
  // category hint
  for (const t of qTokens) {
    if (CATEGORY_HINTS[t] && CATEGORY_HINTS[t] === entry.category) score += 25;
  if (qTokens.length === 1 && CATEGORY_PRIMARY[qTokens[0]] === entry.id) score += 40;
  }
  // previous topic
  if (previousTopic && entry.category === previousTopic) score += 3;
  return score;
}

export function matchQuestion(query, kb, previousTopic = null, locale = 'en') {
  const q = normalize(query);
  if (!q) return { type: 'fallback', entry: null, candidates: [], smallTalk: null };
  if (q.length > 200) return { type: 'fallback', entry: null, candidates: [], smallTalk: null };
  if (isGibberish(q)) return { type: 'fallback', entry: null, candidates: [], smallTalk: null };

  // small talk
  const stLocale = locale || 'en';
  const stList = (SMALL_TALK[stLocale] || []).concat(SMALL_TALK.en || []);
  for (const st of stList) {
    if (st.match.test(q)) return { type: 'smalltalk', entry: null, candidates: [], smallTalk: st.reply };
  }

  const weights = idfWeights(kb);
  const scored = kb.map(e => ({ entry: e, score: scoreEntry(q, e, weights, previousTopic) }))
    .sort((a, b) => b.score - a.score);

  const top = scored[0];
  const second = scored[1];
  const candidates = scored.slice(0, 3).filter(x => x.score > 0);

  // two intents
  let secondIntent = null;
  if (second && second.entry.id !== top.entry.id && second.score >= top.score * 0.55 && second.score >= 8) {
    secondIntent = second.entry;
  }

  if (!top || top.score < 8) {
    return { type: 'fallback', entry: null, candidates: [], smallTalk: null, secondIntent };
  }

  const confidence = top.score >= 24 ? 'high' : (top.score >= 12 ? 'medium' : 'low');

  if (confidence === 'low' || top.entry.needsOwnerAnswer) {
    return { type: 'fallback', entry: top.entry, candidates: candidates.map(x => x.entry), smallTalk: null, secondIntent };
  }
  if (confidence === 'medium') {
    return { type: 'medium', entry: top.entry, candidates: candidates.map(x => x.entry), smallTalk: null, secondIntent };
  }
  return { type: 'high', entry: top.entry, candidates: candidates.map(x => x.entry), smallTalk: null, secondIntent };
}

export function logUnmatched(text) {
  // Stub: no production logging. Strip PII first if wired later.
  const cleaned = String(text || '').replace(/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/gi, '[email]').replace(/\+?\d[\d\s-]{7,}\d/g, '[phone]');
  if (typeof window !== 'undefined' && window.kfaqDebug) console.debug('kfaq unmatched:', cleaned);
}

export function logFeedback(text, helpful) {
  const cleaned = String(text || '').replace(/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/gi, '[email]').replace(/\+?\d[\d\s-]{7,}\d/g, '[phone]');
  if (typeof window !== 'undefined' && window.kfaqDebug) console.debug('kfaq feedback:', helpful, cleaned);
}
