import pkg from '/home/wayne/.npm/_npx/e41f203b7505f1fb/node_modules/playwright/index.js';
const { chromium } = pkg;
import fs from 'node:fs';

const BASE='https://lvh.me';
const ROUTES=['/','/features','/blog','/about','/contact','/school-login','/security','/admissions','/students','/academics'];
const WIDTHS=[375,768,1024,1280,1440,1840,2560];
const SHOT='tests/playwright/footer-screenshots';
fs.mkdirSync(SHOT,{recursive:true});

const browser=await chromium.launch({headless:true});
const context=await browser.newContext({ignoreHTTPSErrors:true});
const page=await context.newPage();

let failures=0;
const rows=[];
const baselines={};

for (const w of WIDTHS) {
  await page.setViewportSize({width:w,height:900});
  // baseline footer html/height from /
  await page.goto(BASE+'/',{waitUntil:'networkidle'});
  const baseHtml=await page.evaluate(()=>{const f=document.querySelector('footer');const c=f.cloneNode(true);c.querySelector('#clk')?.remove();return c.outerHTML;});
  const baseHeight=await page.evaluate(()=>document.querySelector('footer').getBoundingClientRect().height);
  baselines[w]={html:baseHtml,height:baseHeight};

  for (const route of ROUTES) {
    await page.goto(BASE+route,{waitUntil:'networkidle'});
    const r=await page.evaluate((w)=> {
      const footer=document.querySelector('footer');
      const navWrap=document.querySelector('.kf-nav .kf-wrap');
      const footerWrap=footer.querySelector('.kf-wrap') || footer.querySelector('.kf-footer-wrap');
      const fRect=footer.getBoundingClientRect();
      const fwRect=footerWrap.getBoundingClientRect();
      const nwRect=navWrap.getBoundingClientRect();
      const html=footer.cloneNode(true); html.querySelector('#clk')?.remove();
      const normHtml=html.outerHTML;
      const height=footer.getBoundingClientRect().height;
      const scrollW=document.documentElement.scrollWidth;
      const clientW=document.documentElement.clientWidth;
      // columns
      const grid=footer.querySelector('.kf-fgrid');
      const cols=[...grid.children];
      const colTops=cols.map(c=>c.getBoundingClientRect().top);
      const headings=[...footer.querySelectorAll('.kf-fh')];
      const headingStyles=headings.map(h=>{const s=getComputedStyle(h);return {fs:s.fontSize,fw:s.fontWeight,ls:s.letterSpacing,color:s.color,bg:s.backgroundColor,pad:s.padding};});
      // links in columns (navs)
      const navs=[...footer.querySelectorAll('nav')];
      const linkData=navs.map(nav=>{
        const h=nav.querySelector('.kf-fh');
        const links=[...nav.querySelectorAll('a')];
        const hr=h.getBoundingClientRect();
        return {
          headingLeft:hr.left,
          links:links.map(a=>{
            const s=getComputedStyle(a); const r=a.getBoundingClientRect();
            return {left:r.left,textAlign:s.textAlign,display:s.display,width:s.width,paddingLeft:s.paddingLeft,marginLeft:s.marginLeft,textIndent:s.textIndent,top:r.top,height:r.height};
          })
        };
      });
      // bottom bar groups
      const bottom=footer.querySelector('.kf-fbottom');
      const leftGroup=bottom?.querySelector('.kf-fbottom-left');
      const rightGroup=bottom?.querySelector('.kf-fbottom-right');
      const lgRect=leftGroup?.getBoundingClientRect();
      const rgRect=rightGroup?.getBoundingClientRect();
      const backToTop=bottom?.querySelector('.kf-backtotop');
      const bts=backToTop?getComputedStyle(backToTop):null;
      const dock=document.querySelector('.kf-dock');
      const dockRect=dock?.getBoundingClientRect();
      let dockOverlap=false;
      if (dockRect && dockRect.bottom > fRect.top && dockRect.top < fRect.bottom) dockOverlap=true;
      return {normHtml,height,scrollW,clientW,fwRect,nwRect,colTops,headingStyles,linkData,lgRect,rgRect,bts,dockOverlap,footerTop:fRect.top};
    },w);

    // checks
    const base=baselines[w];
    const sameHtml = r.normHtml===base.html;
    const heightOk = Math.abs(r.height-base.height)<=2;
    let containerOk=true;
    let colOk=true;
    let headingOk=true;
    let contentOk=true;
    let bottomOk=true;
    let noScroll = r.scrollW <= r.clientW;
    let backToTopOk=true;

    if (w>=1024) {
      containerOk = Math.abs(r.fwRect.left-r.nwRect.left)<=1 && Math.abs(r.fwRect.right-r.nwRect.right)<=1;
      // columns same top
      const tops=r.colTops;
      const minTop=Math.min(...tops), maxTop=Math.max(...tops);
      if (maxTop-minTop>1) colOk=false;
      // link alignment
      for (const nd of r.linkData) {
        for (const a of nd.links) {
          if (Math.abs(a.left-nd.headingLeft)>0.5) colOk=false;
          if (!(a.textAlign==='left' || a.textAlign==='start')) colOk=false;
          if (parseFloat(a.paddingLeft)!==0 || parseFloat(a.marginLeft)!==0 || parseFloat(a.textIndent)!==0) colOk=false;
        }
        // pitch same
        const tops=nd.links.map(a=>a.top);
        const pitches=[];
        for (let i=1;i<tops.length;i++) pitches.push(tops[i]-tops[i-1]);
        const minP=Math.min(...pitches), maxP=Math.max(...pitches);
        if (pitches.length>1 && maxP-minP>0.5) colOk=false;
      }
      // headings identical
      const hs=r.headingStyles;
      const first=hs[0];
      for (const h of hs) {
        if (h.fs!==first.fs || h.fw!==first.fw || h.ls!==first.ls || h.color!==first.color || h.bg!=='rgba(0, 0, 0, 0)' || parseFloat(h.pad)!==0) headingOk=false;
      }
      // bottom bar same line
      if (r.lgRect && r.rgRect) {
        const lc=r.lgRect.top + r.lgRect.height/2;
        const rc=r.rgRect.top + r.rgRect.height/2;
        if (Math.abs(lc-rc)>2) bottomOk=false;
      }
      // back to top no outline/box-shadow
      if (r.bts) {
        if (r.bts.outlineStyle!=='none' || r.bts.boxShadow!=='none') backToTopOk=false;
      }
    }

    // content checks
    // expected links
    // Terms should not be in Company column: find nav with heading Company
    const companyNav=r.linkData.find? null : null;

    const ok = sameHtml && heightOk && containerOk && colOk && headingOk && bottomOk && noScroll && backToTopOk;
    if (!ok) failures++;
    rows.push({route,width:w,ok,sameHtml,heightOk,containerOk,colOk,headingOk,bottomOk,noScroll,backToTopOk,dockOverlap:r.dockOverlap});
    if ([375,768,1440].includes(w)) {
      await page.screenshot({path:`${SHOT}/${route.replace(/\//g,'_')||'home'}-${w}.png`, fullPage:true});
    }
  }
}

console.log('route | width | result | sameHtml height container col heading bottom noScroll backToTop dockOverlap');
for (const r of rows) console.log(`${r.route} | ${r.width} | ${r.ok?'PASS':'FAIL'} | ${r.sameHtml} ${r.heightOk} ${r.containerOk} ${r.colOk} ${r.headingOk} ${r.bottomOk} ${r.noScroll} ${r.backToTopOk} | overlap:${r.dockOverlap}`);
console.log('Total failures:', failures);
// dock overlap report
const overlaps=rows.filter(r=>r.dockOverlap);
console.log('Dock overlap routes/viewports:', overlaps.length);
await browser.close();
process.exit(failures?1:0);
