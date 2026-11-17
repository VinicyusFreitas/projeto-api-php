// ==========================================
// 1. CONFIGURAÇÃO DA API (Servidor Remoto PHP)
// ==========================================
const PHP_API_URL = 'https://uilian.cloud/alunos/uploads/gustavo_69d4e246e8b36/teste/api.php';


let allGames = [];
let currentGameId = null;


// ==========================================
// GERENCIAMENTO DE USUÁRIOS E SESSÃO
// ==========================================
let users = JSON.parse(localStorage.getItem('nexplay_users')) || [];
let currentUser = JSON.parse(localStorage.getItem('nexplay_current_user')) || null;


function saveUsers() {
   localStorage.setItem('nexplay_users', JSON.stringify(users));
}


function saveCurrentSession() {
   localStorage.setItem('nexplay_current_user', JSON.stringify(currentUser));
}


function getUserFavorites() {
   if (!currentUser) return [];
   const userInList = users.find(u => u.email === currentUser.email);
   return userInList && userInList.favorites ? userInList.favorites : [];
}


function getUserLibrary() {
   if (!currentUser) return [];
   const userInList = users.find(u => u.email === currentUser.email);
   return userInList && userInList.library ? userInList.library : [];
}


function toggleFavorite(gameId) {
   if (!currentUser) {
       alert('Você precisa estar logado para favoritar um jogo.');
       showScreen('screen-login');
       return;
   }
   const userInList = users.find(u => u.email === currentUser.email);
   if (userInList) {
       if (!userInList.favorites) userInList.favorites = [];
       const index = userInList.favorites.indexOf(gameId);
       if (index > -1) {
           userInList.favorites.splice(index, 1);
       } else {
           userInList.favorites.push(gameId);
       }
       saveUsers();
   }
}


function toggleLibrary(gameId) {
   if (!currentUser) {
       alert('Você precisa estar logado para adicionar jogos à biblioteca.');
       showScreen('screen-login');
       return;
   }
   const userInList = users.find(u => u.email === currentUser.email);
   if (userInList) {
       if (!userInList.library) userInList.library = [];
       const index = userInList.library.indexOf(gameId);
       if (index > -1) {
           userInList.library.splice(index, 1);
       } else {
           userInList.library.push(gameId);
       }
       saveUsers();
   }
}


// ==========================================
// TRADUÇÃO DINÂMICA
// ==========================================
async function translateText(text) {
  if (!text || text.trim() === '') return text;


  try {
      const url = `https://translate.googleapis.com/translate_a/single?client=gtx&sl=en&tl=pt&dt=t&q=${encodeURIComponent(text)}`;
      const response = await fetch(url);
      const data = await response.json();


      if (data && data[0]) {
          return data[0].map(item => item[0]).join('');
      }
      return text;
  } catch (error) {
      console.warn('Erro ao traduzir texto dinâmico:', error);
      return text;
  }
}


const genreMap = {
  'Shooter': 'Tiro',
  'MMORPG': 'MMORPG',
  'RPG': 'RPG',
  'ARPG': 'RPG de Ação',
  'Strategy': 'Estratégia',
  'Fighting': 'Luta',
  'Action': 'Ação',
  'Battle Royale': 'Battle Royale',
  'MOBA': 'MOBA',
  'Sports': 'Esportes',
  'Racing': 'Corrida',
  'Card Game': 'Cartas',
  'Card': 'Cartas',
  'Simulation': 'Simulação',
  'Adventure': 'Aventura',
  'Social': 'Social',
  'Fantasy': 'Fantasia'
};


const platformMap = {
  'PC (Windows)': 'PC (Windows)',
  'Web Browser': 'Navegador Web',
  'PC (Windows), Web Browser': 'PC e Navegador'
};


function translateGenre(genre) {
  if (!genre) return 'Geral';
  return genreMap[genre.trim()] || genre;
}


function translatePlatform(platform) {
  if (!platform) return 'PC';
  return platformMap[platform.trim()] || platform;
}


// ==========================================
// 2. SELEÇÃO DE ELEMENTOS DO DOM
// ==========================================
const gamesContainer = document.getElementById('games-container');
const browseContainer = document.getElementById('browse-container');
const favoritesContainer = document.getElementById('favorites-container');
const libraryContainer = document.getElementById('library-container');


const mainSearchInput = document.getElementById('main-search');
const categoryPills = document.querySelectorAll('.categories-scroll .pill');
const screens = document.querySelectorAll('.screen');
const navLinks = document.querySelectorAll('.sidebar-nav .nav-link');


const navCatalog = document.getElementById('nav-catalog');
const navBrowse = document.getElementById('nav-browse');
const navFavorites = document.getElementById('nav-favorites');
const navLibrary = document.getElementById('nav-library');


const btnBackDetail = document.getElementById('btn-back-detail');
const linkGoRegister = document.getElementById('link-go-register');
const linkGoLogin = document.getElementById('link-go-login');


const btnAddFavorite = document.getElementById('btn-add-favorite');
const btnAddLibrary = document.getElementById('btn-add-library');
const txtAddFavorite = document.getElementById('txt-add-favorite');
const txtAddLibrary = document.getElementById('txt-add-library');
const iconAddFavorite = document.getElementById('icon-add-favorite');


const formLogin = document.getElementById('form-login');
const formRegister = document.getElementById('form-register');


function setElementText(id, text) {
  const el = document.getElementById(id);
  if (el) el.innerText = text;
}


// ==========================================
// 3. NAVEGAÇÃO ENTRE TELAS (SPA)
// ==========================================
function setActiveNavLink(activeId) {
  navLinks.forEach(link => {
      if (link.id === activeId) {
          link.classList.add('active');
      } else {
          link.classList.remove('active');
      }
  });
}


function showScreen(screenId, activeNavId = null) {
  if (!screens.length) return;
  screens.forEach(screen => screen.classList.remove('active'));
  const targetScreen = document.getElementById(screenId);
  if (targetScreen) {
      targetScreen.classList.add('active');
      window.scrollTo({ top: 0, behavior: 'smooth' });
  }
  if (activeNavId) {
      setActiveNavLink(activeNavId);
  }
}


if (navCatalog) navCatalog.addEventListener('click', (e) => { e.preventDefault(); showScreen('screen-home', 'nav-catalog'); });
if (navBrowse) navBrowse.addEventListener('click', (e) => { e.preventDefault(); renderBrowseScreen(); showScreen('screen-browse', 'nav-browse'); });
if (navFavorites) navFavorites.addEventListener('click', (e) => { e.preventDefault(); renderFavoritesScreen(); showScreen('screen-favorites', 'nav-favorites'); });
if (navLibrary) navLibrary.addEventListener('click', (e) => { e.preventDefault(); renderLibraryScreen(); showScreen('screen-library', 'nav-library'); });


if (btnBackDetail) btnBackDetail.addEventListener('click', () => showScreen('screen-home', 'nav-catalog'));
if (linkGoRegister) linkGoRegister.addEventListener('click', (e) => { e.preventDefault(); showScreen('screen-register'); });
if (linkGoLogin) linkGoLogin.addEventListener('click', (e) => { e.preventDefault(); showScreen('screen-login'); });


// ==========================================
// 4. SESSÃO DO USUÁRIO
// ==========================================
function updateUIUserSession() {
   const userAvatarEl = document.querySelector('.user-avatar');
   const userNameEl = document.querySelector('.user-name');
   const userStatusEl = document.querySelector('.user-status');
   const topbarActions = document.querySelector('.topbar-actions');


   if (currentUser) {
       const nameParts = currentUser.username.trim().split(' ');
       const initials = nameParts.length > 1
           ? (nameParts[0][0] + nameParts[1][0]).toUpperCase()
           : currentUser.username.slice(0, 2).toUpperCase();


       if (userAvatarEl) userAvatarEl.innerText = initials;
       if (userNameEl) userNameEl.innerText = currentUser.username;
       if (userStatusEl) userStatusEl.innerText = 'Conectado';


       if (topbarActions) {
           topbarActions.innerHTML = `
               <button class="btn btn-outline" id="btn-topbar-logout">Sair</button>
           `;
           document.getElementById('btn-topbar-logout').addEventListener('click', logoutUser);
       }
   } else {
       if (userAvatarEl) userAvatarEl.innerText = '--';
       if (userNameEl) userNameEl.innerText = 'Visitante';
       if (userStatusEl) userStatusEl.innerText = 'Desconectado';


       if (topbarActions) {
           topbarActions.innerHTML = `
               <button class="btn btn-outline" id="btn-topbar-login">Entrar</button>
               <button class="btn btn-primary" id="btn-topbar-register">Criar conta</button>
           `;
           document.getElementById('btn-topbar-login').addEventListener('click', () => showScreen('screen-login'));
           document.getElementById('btn-topbar-register').addEventListener('click', () => showScreen('screen-register'));
       }
   }
}


function logoutUser() {
   currentUser = null;
   localStorage.removeItem('nexplay_current_user');
   updateUIUserSession();
   alert('Sessão encerrada com sucesso.');
   showScreen('screen-home', 'nav-catalog');
}


// ==========================================
// 5. BUSCAR E RENDERIZAR JOGOS
// ==========================================
async function fetchGames(query = '') {
  if (!gamesContainer) return;


  gamesContainer.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: var(--text-muted);">A carregar jogos...</p>';


  try {
      let url = PHP_API_URL + '?action=games';
      if (query) {
          url += '&search=' + encodeURIComponent(query);
      }


      const response = await fetch(url);
      if (!response.ok) throw new Error('Erro PHP: ' + response.status);


      const data = await response.json();


      if (!Array.isArray(data)) {
          throw new Error('A resposta do servidor não é uma lista válida.');
      }


      allGames = data.map(game => {
          // Tratamento de URL de imagem forçando HTTPS de forma segura
          let rawUrl = game.thumbnail || '';
          if (rawUrl.startsWith('http://')) {
              rawUrl = rawUrl.replace('http://', 'https://');
          }


          return {
              id: game.id,
              title: game.title,
              developer: game.developer || 'Estúdio',
              publisher: game.publisher || 'Distribuidora',
              genre: translateGenre(game.genre),
              price: 0,
              rating: (Math.random() * (4.9 - 4.1) + 4.1).toFixed(1),
              releaseDate: game.release_date || '--',
              platform: translatePlatform(game.platform),
              coverUrl: rawUrl,
              shortDescription: game.short_description || 'Sem descrição disponível.',
              gameUrl: game.game_url
          };
      });


      renderGames(allGames, gamesContainer);


  } catch (error) {
      console.error('Erro ao carregar jogos:', error);
      gamesContainer.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: #ef4444;">Erro ao carregar os jogos. Verifique a consola.</p>';
  }
}


function renderGames(list, container = gamesContainer) {
  if (!container) return;


  container.innerHTML = '';


  if (!list || list.length === 0) {
      container.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: var(--text-muted);">Nenhum jogo encontrado.</p>';
      return;
  }


  list.forEach(game => {
      const card = document.createElement('div');
      card.className = 'game-card';


      // Define a imagem principal com fallback de reserva caso a imagem falhe
      const imageSrc = game.coverUrl ? game.coverUrl : 'https://placehold.co/260x160/1c1e26/ffffff?text=NexPlay';


      card.innerHTML = `
          <div class="game-thumb" style="background-image: url('${imageSrc}');"></div>
          <div class="game-info">
              <div class="game-header-info">
                  <h3 class="game-title">${game.title}</h3>
                  <div class="game-rating">
                      <i class="fa-solid fa-star"></i>
                      <span>${game.rating}</span>
                  </div>
              </div>
              <div class="game-genre">${game.genre}</div>
              <div class="game-price">Grátis</div>
          </div>
      `;


      card.addEventListener('click', () => openGameDetails(game.id));
      container.appendChild(card);
  });
}


function renderBrowseScreen() {
  renderGames(allGames, browseContainer);
}


function renderFavoritesScreen() {
  if (!currentUser) {
      favoritesContainer.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: var(--text-muted);">Faça login para ver seus jogos favoritos.</p>';
      return;
  }
  const userFavs = getUserFavorites();
  const favGames = allGames.filter(g => userFavs.includes(g.id));
  renderGames(favGames, favoritesContainer);
}


function renderLibraryScreen() {
  if (!currentUser) {
      libraryContainer.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: var(--text-muted);">Faça login para ver sua biblioteca.</p>';
      return;
  }
  const userLib = getUserLibrary();
  const libGames = allGames.filter(g => userLib.includes(g.id));
  renderGames(libGames, libraryContainer);
}


// ==========================================
// 6. DETALHES DO JOGO
// ==========================================
async function openGameDetails(gameId) {
    currentGameId = gameId;
    let localGame = allGames.find(g => g.id === gameId);
 
    let localTitle = localGame ? localGame.title : 'Jogo';
    let localDev = localGame ? localGame.developer : 'Desenvolvedor';
    let localGenre = localGame ? localGame.genre : 'Gênero';
    let localDesc = localGame ? localGame.shortDescription : 'Sem descrição disponível.';
    let localRel = localGame ? localGame.releaseDate : '--';
    let localPlat = localGame ? localGame.platform : '--';
    let localPub = localGame ? localGame.publisher : '--';
    let localRating = localGame ? localGame.rating : '4.5';
    let localCover = localGame ? localGame.coverUrl : '';
 
    // Aplica a imagem do jogo no fundo do banner
    const detailBanner = document.querySelector('.detail-banner-desktop');
    if (detailBanner) {
        if (localCover) {
            detailBanner.style.backgroundImage = `url('${localCover}')`;
        } else {
            detailBanner.style.backgroundImage = ''; // Volta pro roxo se não tiver imagem
        }
    }
 
    updateActionButtonsState();
 
    setElementText('detail-title', localTitle);
    setElementText('detail-developer', localDev + ' · ' + localGenre);
    setElementText('detail-desc', 'A traduzir descrição...');
    setElementText('detail-price', 'Grátis');
    setElementText('detail-rating', localRating);
    showScreen('screen-detail');
 
    try {
        const response = await fetch(PHP_API_URL + '?action=detail&id=' + encodeURIComponent(gameId));
        const detailData = await response.json();
 
        // Se a API de detalhes trouxer uma screenshot ou thumbnail melhor, atualiza o banner
        let bannerImg = localCover;
        if (detailData.screenshots && detailData.screenshots.length > 0 && detailData.screenshots[0].image) {
            bannerImg = detailData.screenshots[0].image.replace('http://', 'https://');
        } else if (detailData.thumbnail) {
            bannerImg = detailData.thumbnail.replace('http://', 'https://');
        }
 
        if (detailBanner && bannerImg) {
            detailBanner.style.backgroundImage = `url('${bannerImg}')`;
        }
 
        const rawDescription = detailData.description || detailData.short_description || localDesc;
        const translatedDesc = await translateText(rawDescription);
 
        const translatedGenre = translateGenre(detailData.genre) || localGenre;
        const translatedPlat = translatePlatform(detailData.platform) || localPlat;
 
        setElementText('detail-title', detailData.title || localTitle);
        setElementText('detail-developer', (detailData.developer || localDev) + ' · ' + translatedGenre);
        setElementText('detail-desc', translatedDesc);
       
        let storageReq = 'Varia com a plataforma';
        if (detailData.minimum_system_requirements && detailData.minimum_system_requirements.storage) {
            storageReq = detailData.minimum_system_requirements.storage;
        }
 
        setElementText('spec-release', detailData.release_date || localRel);
        setElementText('spec-size', storageReq);
        setElementText('spec-platform', translatedPlat);
        setElementText('spec-publisher', detailData.publisher || localPub);
 
        const tagsContainer = document.getElementById('detail-tags');
        if (tagsContainer) {
            tagsContainer.innerHTML = `
                <span class="tag">${translatedGenre}</span>
                <span class="tag">${translatedPlat}</span>
            `;
        }
 
    } catch (error) {
        console.error('Erro ao carregar detalhes:', error);
        const translatedLocalDesc = await translateText(localDesc);
        setElementText('detail-desc', translatedLocalDesc);
    }
 }


// ==========================================
// 7. BOTÕES DE AÇÃO
// ==========================================
function updateActionButtonsState() {
  if (!currentGameId) return;


  const isFav = getUserFavorites().includes(currentGameId);
  const isLib = getUserLibrary().includes(currentGameId);


  if (isFav) {
      txtAddFavorite.innerText = 'Remover dos Favoritos';
      iconAddFavorite.className = 'fa-solid fa-heart';
      btnAddFavorite.style.color = '#ef4444';
  } else {
      txtAddFavorite.innerText = 'Adicionar aos Favoritos';
      iconAddFavorite.className = 'fa-regular fa-heart';
      btnAddFavorite.style.color = 'inherit';
  }


  if (isLib) {
      txtAddLibrary.innerText = 'Na Biblioteca';
      btnAddLibrary.style.backgroundColor = '#10b981';
  } else {
      txtAddLibrary.innerText = 'Adicionar à biblioteca';
      btnAddLibrary.style.backgroundColor = '';
  }
}


if (btnAddFavorite) {
  btnAddFavorite.addEventListener('click', () => {
      if (!currentGameId) return;
      toggleFavorite(currentGameId);
      updateActionButtonsState();
  });
}


if (btnAddLibrary) {
  btnAddLibrary.addEventListener('click', () => {
      if (!currentGameId) return;
      toggleLibrary(currentGameId);
      updateActionButtonsState();
  });
}


// ==========================================
// 8. BUSCA E FILTROS
// ==========================================
let searchTimer;
if (mainSearchInput) {
  mainSearchInput.addEventListener('input', (e) => {
      clearTimeout(searchTimer);
      const searchTerm = e.target.value.trim();
    
      searchTimer = setTimeout(() => {
          fetchGames(searchTerm);
      }, 400);
  });
}


categoryPills.forEach(pill => {
  pill.addEventListener('click', () => {
      categoryPills.forEach(p => p.classList.remove('active'));
      pill.classList.add('active');


      const category = pill.innerText.trim();
      if (category === 'Todos') {
          renderGames(allGames);
      } else {
          const filtered = allGames.filter(game =>
              game.genre.toLowerCase().includes(category.toLowerCase())
          );
          renderGames(filtered);
      }
  });
});


// ==========================================
// 9. CADASTRO E LOGIN
// ==========================================
if (formRegister) {
   formRegister.addEventListener('submit', (e) => {
       e.preventDefault();


       const username = document.getElementById('reg-user').value.trim();
       const email = document.getElementById('reg-email').value.trim().toLowerCase();
       const pass = document.getElementById('reg-pass').value;
       const passConfirm = document.getElementById('reg-pass-confirm').value;


       if (pass !== passConfirm) {
           alert('As senhas não coincidem!');
           return;
       }


       const userExists = users.some(u => u.email === email);
       if (userExists) {
           alert('Já existe uma conta cadastrada com este e-mail.');
           return;
       }


       const newUser = { username, email, password: pass, favorites: [], library: [] };
       users.push(newUser);
       saveUsers();


       currentUser = { username, email };
       saveCurrentSession();
       updateUIUserSession();


       alert('Conta criada com sucesso!');
       formRegister.reset();
       showScreen('screen-home', 'nav-catalog');
   });
}


if (formLogin) {
   formLogin.addEventListener('submit', (e) => {
       e.preventDefault();


       const email = document.getElementById('login-email').value.trim().toLowerCase();
       const pass = document.getElementById('login-password').value;


       const foundUser = users.find(u => u.email === email && u.password === pass);


       if (!foundUser) {
           alert('E-mail ou senha incorretos.');
           return;
       }


       currentUser = { username: foundUser.username, email: foundUser.email };
       saveCurrentSession();
       updateUIUserSession();


       alert(`Bem-vindo de volta, ${foundUser.username}!`);
       formLogin.reset();
       showScreen('screen-home', 'nav-catalog');
   });
}


// ==========================================
// 10. INICIALIZAÇÃO
// ==========================================
document.addEventListener('DOMContentLoaded', () => {
   updateUIUserSession();
   fetchGames();
});
