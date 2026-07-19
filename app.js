const universes = [
  ['💥','Comics','Heroes, indies, graphic novels','#651fff','#e53935'],
  ['🗡️','Fantasy','Magic, quests, myths, worlds','#00695c','#43a047'],
  ['🚀','Sci-Fi','Space, cyberpunk, robots, futures','#0d47a1','#00b8d4'],
  ['👾','Gaming','Console, PC, retro, indie','#311b92','#7c4dff'],
  ['🌸','Anime & Manga','Series, studios, art, cosplay','#ad1457','#ff80ab'],
  ['🎲','Tabletop','RPGs, cards, minis, board games','#e65100','#ffb300'],
  ['🧟','Horror','Monsters, paranormal, slashers','#3e0000','#b71c1c'],
  ['🦸','Cosplay','Armor, props, wigs, performance','#004d40','#26a69a']
];
const booths = [
  ['Nova Forge Props','Cosplay','Custom helmets, armor, and prop commissions','#133b5c','#8e44ad'],
  ['Panel Break Comics','Comics','Independent comics and limited-run variants','#8b0000','#ff6f00'],
  ['Moon Rabbit Studio','Anime','Art prints, pins, stickers, and commissions','#3f2b96','#a8c0ff'],
  ['Critical Loot Co.','Tabletop','Dice, towers, miniatures, and campaign gear','#00695c','#80cbc4'],
  ['Retro Warp Games','Gaming','Restored games, consoles, and collectibles','#1a237e','#ff4081'],
  ['Crypt Cabinet','Horror','Oddities, monster art, masks, and decor','#111','#8b0000']
];
const events = [
  ['8:30 PM ET','Cosplay Stage','Foam Smithing Without Expensive Tools'],
  ['Tomorrow','Anime Theater','Independent Animation Showcase'],
  ['Saturday','Tabletop Hall','Live One-Shot: The Clockwork Crypt']
];
const artists = [
  ['Kira Vale','Fantasy Illustrator','#50207a','#e45dcb'],
  ['Marcus Hex','Creature Designer','#09203f','#537895'],
  ['Juno Sparks','Comic Artist','#8e0e00','#1f1c18']
];
const courses = [
  ['⚔️','Lightsaber Training','Movement, safety, and choreography','#0f2027','#2c5364'],
  ['🧵','Cosplay Sewing','Patterns, fabrics, fittings, and finishing','#42275a','#734b6d'],
  ['🪖','Foam Armor 101','Build wearable armor from EVA foam','#134e5e','#71b280'],
  ['🖌️','Miniature Painting','Brush control, shading, and basing','#5f2c82','#49a09d']
];
const products = [
  ['🛡️','Hand-Painted Space Knight Shield','$145','#1d2671','#c33764'],
  ['📚','Signed Indie Graphic Novel Set','$42','#42275a','#734b6d'],
  ['🎲','Nebula Resin Dice Collection','$68','#0f2027','#2c5364'],
  ['🤖','Retro Robot Desk Figure','$85','#3a1c71','#d76d77']
];

const byId = id => document.getElementById(id);
byId('universe-grid').innerHTML = universes.map(([icon,name,desc,c1,c2]) => `<article class="universe-card" style="--u1:${c1};--u2:${c2}" data-search="${name}"><div class="universe-icon">${icon}</div><h3>${name}</h3><p>${desc}</p></article>`).join('');
byId('booth-filters').innerHTML = ['All','Comics','Cosplay','Anime','Tabletop','Gaming','Horror'].map((x,i)=>`<button class="${i===0?'active':''}" data-filter="${x}">${x}</button>`).join('');
function renderBooths(filter='All') { byId('booth-grid').innerHTML = booths.filter(b=>filter==='All'||b[1]===filter).map(([name,cat,desc,c1,c2])=>`<article class="booth-card"><div class="booth-cover" style="--b1:${c1};--b2:${c2}"><span>${cat}</span></div><div class="booth-body"><h3>${name}</h3><p>${desc}</p><div class="booth-meta"><span>★ Featured Booth</span><span>Visit →</span></div></div></article>`).join(''); }
renderBooths();
byId('event-list').innerHTML = events.map(([time,stage,title])=>`<article><p class="event-meta">${time} • ${stage}</p><h3>${title}</h3><p>Save to schedule →</p></article>`).join('');
byId('artist-cards').innerHTML = artists.map(([name,role,c1,c2])=>`<article class="artist-card" style="--a1:${c1};--a2:${c2}"><div><h3>${name}</h3><span>${role}</span></div></article>`).join('');
byId('academy-grid').innerHTML = courses.map(([icon,name,desc,c1,c2])=>`<article class="academy-card"><div class="academy-thumb" style="--c1:${c1};--c2:${c2}">${icon}</div><div><h3>${name}</h3><p>${desc}</p><strong>Start learning →</strong></div></article>`).join('');
byId('product-grid').innerHTML = products.map(([icon,name,price,c1,c2])=>`<article class="product-card"><div class="product-thumb" style="--c1:${c1};--c2:${c2}">${icon}</div><div><h3>${name}</h3><p>Ships from a verified booth</p><span class="price">${price}</span></div></article>`).join('');

document.querySelectorAll('[data-filter]').forEach(btn=>btn.addEventListener('click',()=>{document.querySelectorAll('[data-filter]').forEach(x=>x.classList.remove('active'));btn.classList.add('active');renderBooths(btn.dataset.filter)}));
document.querySelectorAll('[data-search]').forEach(el=>el.addEventListener('click',()=>{byId('search-input').value=el.dataset.search||el.textContent.trim();byId('booths').scrollIntoView()}));
byId('hero-search').addEventListener('submit',e=>{e.preventDefault(); const q=byId('search-input').value.trim(); if(q) openModal('search',q);});

const modal = byId('modal');
function openModal(type,data=''){
  const content=byId('modal-content');
  if(type==='signin') content.innerHTML=`<p class="eyebrow">WELCOME BACK</p><h2 id="modal-title">Sign In</h2><label>Email<input type="email" placeholder="you@example.com"></label><label>Password<input type="password" placeholder="••••••••"></label><button class="button primary" style="width:100%">Enter the Multiverse</button>`;
  if(type==='booth') content.innerHTML=`<p class="eyebrow">BECOME AN EXHIBITOR</p><h2 id="modal-title">Open a Booth</h2><label>Booth or brand name<input placeholder="Your business name"></label><label>Primary category<select><option>Comics</option><option>Cosplay</option><option>Anime</option><option>Gaming</option><option>Tabletop</option><option>Art</option></select></label><label>Email<input type="email" placeholder="you@example.com"></label><button class="button primary" style="width:100%">Start Booth Application</button>`;
  if(type==='search') content.innerHTML=`<p class="eyebrow">SEARCH RESULTS</p><h2 id="modal-title">Explore “${data}”</h2><p>Search is wired for the prototype. The production version will return matching universes, booths, brands, products, panels, and classes.</p><button class="button primary" data-close-modal>Continue Exploring</button>`;
  modal.classList.add('open');modal.setAttribute('aria-hidden','false');
}
document.querySelectorAll('[data-modal]').forEach(btn=>btn.addEventListener('click',()=>openModal(btn.dataset.modal)));
document.addEventListener('click',e=>{if(e.target.matches('[data-close-modal]')){modal.classList.remove('open');modal.setAttribute('aria-hidden','true')}});
document.querySelector('.menu-toggle').addEventListener('click',e=>{const nav=byId('main-nav');const open=nav.style.display==='flex';nav.style.display=open?'none':'flex';nav.style.position='absolute';nav.style.top='82px';nav.style.left='0';nav.style.right='0';nav.style.padding='20px';nav.style.background='#080914';nav.style.flexDirection='column';e.currentTarget.setAttribute('aria-expanded',String(!open));});
