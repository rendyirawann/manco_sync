# Sanka Vollerei API — Full Endpoint Catalog

> **Base host:** `https://www.sankavollerei.web.id` (the `.com` host 301-redirects here).
> All scraper endpoints require a **browser `User-Agent`** header (they 403 non-browser agents).
> Captured from the official docs pages (2026-07). Kept in-repo so we never have to re-screenshot.
>
> **App routing (by content type / API namespace):**
> - `/anime/*` (Japanese anime) → app route **anime**
> - `/anime/donghua/*`, `/anime/donghub/*`, `/anime/kura/quick/donghua` → app route **donghua**
> - `/comic/*` (manga/manhwa/manhua) → app route **/comic**
> - `/movie/api/*` film sources → app route **film/movie**
> - `/movie/api/*` drama sources → app route **drama**
> - `/novel/*` → app route **novel**
>
> Envelopes vary per source ({ok,data} vs top-level keys); id field = `animeId|slug|bookId|id`;
> poster = `poster|thumbnail|image|cover|coverWap`. Normalized by `App\Services\Portal\SourceClient`.

---

## 1. ANIME  (namespace `/anime`)

**Otakudesu** `/anime`: `home` · `schedule` · `ongoing-anime` · `complete-anime` · `anime/:slug` (detail) · `episode/:slug` (stream+download) · `server/:serverId` (resolve embed) · `search/:keyword` · `genre` · `genre/:slug` · `batch/:slug` · `unlimited`
**Samehadaku** `/anime/samehadaku`: `home` · `recent` · `ongoing` · `completed` · `popular` · `movies` · `list` · `schedule` · `search?q=` · `genres` · `genres/:genreId` · `anime/:animeId` · `episode/:episodeId` · `server/:serverId` · `batch` · `batch/:batchId`
**Animasu** `/anime/animasu`: `home` · `popular` · `movies` · `ongoing` · `completed` · `latest` · `search/:keyword` · `animelist` · `advanced-search` · `genres` · `genre/:slug` · `characters` · `character/:slug` · `schedule` · `detail/:slug` · `episode/:slug`
**Kusonime** `/anime/kusonime`: `latest` · `all-anime` · `movie` · `type/:type` · `all-genres` · `all-seasons` · `search/:query` · `genre/:slug` · `season/:season/:year` · `detail/:slug`
**Anoboy** `/anime/anoboy`: `home` · `search/:keyword` · `anime/:slug` · `episode/:slug` · `az-list` · `list` · `genre/:slug` · `genres`
**Oploverz** `/anime/oploverz`: `home` · `schedule` · `ongoing` · `completed` · `list` · `search/:query` · `anime/:slug` · `episode/:slug`
**Stream (Anime Indo)** `/anime/stream`: `latest/:page?` · `popular` · `search/:query` · `anime/:slug` · `episode/:slug` · `movie/:page?` · `list` · `genres` · `genres/:slug/:page?`
**Animekuindo** `/anime/animekuindo`: `home` · `schedule` · `latest` · `popular` · `movie` · `search/:query` · `genres` · `genres/:slug` · `seasons` · `seasons/:slug` · `detail/:slug` · `episode/:slug`
**Nimegami** `/anime/nimegami`: `home` · `search/:query` · `detail/:slug` · `anime-list` · `genre/list` · `genre/:slug` · `seasons/list` · `seasons/:slug` · `type/list` · `type/:slug` · `j-drama` · `live-action` · `live-action/:slug` · `drama/:slug`
**Alqanime** `/anime/alqanime`: `home` · `schedule` · `popular` · `list` · `ongoing` · `completed` · `movie` · `search/:query` · `genres` · `genre/:slug` · `season/:slug` · `detail/:slug`
**Winbu** `/anime/winbu`: `home` · `search` · `anime/:id` · `series/:id` · `film/:id` · `episode/:id` · `server` · `animedonghua` · `film` · `series` · `tvshow` · `others` · `genres` · `genre/:slug` · `catalog` · `schedule` · `update` · `latest` · `ongoing` · `completed` · `populer` · `all-anime` · `all-anime-reverse` · `list`
**Animekompi** `/anime/animekompi`: `home` · `terbaru` · `donghua` · `live-action` · `tokusatsu` · `movie` · `schedule` · `list` · `search?q=` · `search/suggest?q=` · `filter` · `filterlist` · `genres`/`seasons`/`studios`/`status`/`types`/`orders` · `genre|season|studio|status|type|order/:slug` · `detail/:slug` · `episode/:slug` · `tooltip/:id`
**Kuramanime** `/anime/kura`: `home` · `search/:keyword` · `anime/:id/:slug` · `watch/:id/:slug/:episode` · `batch/:id/:slug/:batchId` · `anime-list` · `schedule` · `quick/{popular,ongoing,finished,movie,donghua}` · `properties/{genre,season,studio,type,quality,source,country}[/:slug]`
**Dramabox** `/anime/dramabox`: `search?q=` · `latest` · `trending` · `detail?bookId=` · `stream?bookId=&episode=` · `auth/refresh`
**Drachin** `/anime/drachin`: `home` · `latest` · `popular` · `search/:query` · `detail/:slug` · `episode/:slug`
**Nekopoi (18+)** `/anime/neko`: `latest` · `release/:page` · `search/:query` · `get?url=` · `random`

## 2. DONGHUA  (Chinese animation — video)

**Anichin** `/anime/donghua`: `home/:page?` · `ongoing/:page?` · `completed/:page?` · `latest/:page?` · `schedule` · `az-list/:slug/:page?` · `search/:keyword/:page?` · `detail/:slug` · `episode/:slug` · `genres` · `genres/:slug/:page?` · `seasons/:year?`
  - episode → `streaming.main_url.url` = **OK.ru iframe embed** (`https://ok.ru/videoembed/{id}`, no X-Frame-Options → iframe OK). Also `streaming.servers[]` (OK.ru/Dailymotion/Archive/Mega), `download_url`, `navigation`. Confirmed reachable from plain PHP (JSON routes not Cloudflare-walled). Brand-new episodes may momentarily fail → retry.
**Donghub** `/anime/donghub`: `home` · `latest` · `popular` · `movie` · `schedule` · `search/:query` · `genre/:slug` · `list?:slug` · `detail/:slug` · `episode/:slug`
**Kuramanime donghua** `/anime/kura/quick/donghua`

## 3. COMIC — manga / manhwa / manhua  (namespace `/comic`)

**Main aggregator** `/comic`: `unlimited` (6297+ deep crawl) · `scroll` · `terbaru` (latest) · `populer` · `trending` · `homepage` (popular+latest+ranking) · `search?q=` · `comic/:slug` (detail+chapters) · `chapter/:slug` (read images) · `chapter/:slug/navigation` · `type/:type` (manga/manhwa/manhua) · `genres` · `genre/:genre` · `browse` (filter type/order/genre) · `advanced-search` · `recommendations` · `random` · `berwarna/:page` (colored) · `pustaka/:page` · `infinite` · `stats`/`fullstats`/`analytics`/`docs`/`health`
**BacaKomik** `/comic/bacakomik`: `latest` · `populer` · `only/:type` · `top` · `list` · `search/:query` · `genres` · `genre/:genre` · `detail/:slug` · `chapter/:slug` · `recomen` · `komikberwarna/:page`
**Komikstation** `/comic/komikstation`: `home` · `list` · `popular?page=` · `recommendation` · `top-weekly` · `ongoing?page=` · `az-list/:letter` · `genres` · `genre/:slug/:page` · `search/:query/:page` · `manga/:slug` (detail) · `chapter/:slug`
**Maid Comic** `/comic/maid`: `api` (home) · `list` · `latest?page=` · `manga` (detail) · `chapter` · `genres` · `genres/:slug?page=` · `search/:slug&page=`
**Komikindo** `/comic/komikindo`: `config` · `list` · `latest/:page` · `populer/:page` · `type/:type/:page` · `colorized/:val/:page` · `search/:query/:page` · `detail/:id` · `chapter/:id` · `genres` · `filter/:term/:val/:page`
**Mangakita** `/comic/mangakita`: `home` · `list` · `projects/:page?` · `daftar-manga/:page?` · `genres` · `genres/:slug/:page?` · `rekomendasi` · `search/:query/:page?` · `detail/:slug` · `chapter/:slug`
**SoulScans** `/comic/soulscan`: `home` · `projects/:page?` · `list` · `all` · `azlist/:letter?` · `search/:query` · `detail/:slug` · `chapter/:slug`
**Bacaman** `/comic/bacaman`: `home` · `list` · `search/:query` · `detail/:slug` · `chapter/:slug` · `popular` · `latest` · `update` · `completed` · `genres` · `genres/:slug` · `type/:type` · `az/:page?`
**Meganei** `/comic/meganei`: `home/:page` · `list?page=` · `search/:query` · `info/:slug` (detail)
**Softkomik** `/comic/softkomik`: `home` · `list` · `update` · `ongoing` · `completed` · `library` · `type/:type` · `search` · `genres` · `genre/:name` · `detail/:slug` · `chapter/:slug/:num?`
**Westmanga** `/comic/westmanga`: `home` · `genres` · `list` · `latest` · `popular` · `ongoing` · `completed` · `manga` · `manhua` · `manhwa` · `az` · `za` · `added` · `colored` · `uncolored` · `projects` · `others` · `genre/:id` · `genres-filter` · `search?q=` · `detail/:slug` · `chapter/:slug`
**Mangasusuku** `/comic/mangasusuku`: `home/:page?` · `latest/:page?` · `popular/:page?` · `list/:page?` · `list-by-char/:char/:page?` · `search/:query/:page?` · `genres` · `genre/:genreId/:page?` · `detail/:slug` · `chapter/:slug`
**Kiryuu** `/comic/kiryuu` *(marked error)*: `home` · `popular` · `recommendations` · `latest` · `top-weekly` · `search/:query/:page?` · `manga/:slug` · `chapter/:slug`
**Cosmic Scans** `/comic/cosmic` *(region lock)*: `home` · `projects/:page?` · `latest/:page?` · `search/:query/:page?` · `manga/:slug` · `chapter/:slug`

## 4. NOVEL  (namespace `/novel`)

**Main** `/novel`: `home` · `hot-search` · `search?q=` · `genre/:id` · `chapters/:novelId`
**SakuraNovel** `/novel/sakuranovel`: `home` · `search?q=` · `advanced-search` · `detail/:slug` · `read/:slug` · `genres` · `genre/:slug` · `tags` · `tag/:slug` · `daftar-novel`

## 5. MOVIE / DRAMA / FILM  (namespace `/movie/api`)

> Drama sources (short-drama & Asian drama) → app route **drama**. Film/TV sources → app route **film**.

**DramaBox** `/movie/api/dramabox`: `latest` · `trending` · `search?q=` · `detail?bookId=` · `stream?bookId=&episode=` (m3u8/mp4) · `auth/refresh`
**DramaboxV2** `/movie/api/dramaboxv2`: `home` · `latest` · `popular` · `foryou` · `vip` · `random` · `search?keyword=&lang=` · `search/suggest` · `popular/search` · `sulih-suara` · `recommend` · `detail/:bookId` · `detail/:bookId/v2` · `chapters/:bookId` · `stream?bookId=&episode=` · `download/:bookId` · `categories` · `category/:id` · `list-lang` · `list-custom` · `custom-drama` · `auth/refresh`
**DramaDash** `/movie/api/dramadash`: `home` · `search?q=` · `detail/:id` · `watch?id=&e=` · `auth/refresh`
**Melolo** `/movie/api/melolo`: `search?q=` · `trending` · `latest` · `recommend` · `detail?id=` · `watch?vid=` · `auth/refresh`
**NetShort** `/movie/api/netshort`: `search?q=` · `explore` · `discover` · `categories` · `detail/:id` · `watch/:id/ep/:ep` · `config/{constants,tags,regions,audio,sort}` · `auth/refresh`
**Flickreel** `/movie/api/flickreel`: `home` · `latest` · `hot-rank` · `event` · `romance` · `recommend` · `ranking` · `search?q=` · `detail?id=` · `stream?id=&chapter_id=` (HLS) · `auth/refresh`
**Dramawave** `/movie/api/dramawave`: `tabs` · `tab/:id` · `search?q=` · `search/suggest` · `foryou` · `category/{popular,upcoming,event,new,exclusive,dubbing,vip,female,male,free,anime}` · `hot-words` · `hot-list` · `detail/:id` · `auth/refresh`
**NontonDrakor** `/movie/api/nontondrakor`: `slider` · `latest` · `drakor` (Korea) · `drachin` (China) · `reality` · `movies` · `search?q=` · `genre/:id` · `detail?id=&type=` · `auth/refresh`
**Drakor** `/movie/api/drakor`: `home` · `latest` · `ongoing` · `complete` · `search?q=` · `detail/:slug` · `genres` · `genre/:genre` · `country/:country` · `year/:year` · `rating/:rating` · `filters` · `type/:type` · `series/:slug` · `network/:network`
**DrakorID** `/movie/api/drakorid`: `latest` · `ongoing` · `detail/:id` · `watch/:id` · `search?q=` · `genres` · `genre/:id` · `all` · `types` · `auth/refresh`
**FilmApik** `/movie/api/filmapik`: `home` · `latest` · `trending` · `top-imdb` · `ratings` · `search?q=` · `detail/:slug` · `watch/episode/:slug` · `category/:cat` (action/adventure/comedy/crime/drama/family/horror/science-fiction/thriller/box-office) · `drama-{korea,china,jepang,thailand,india,singapore,west,other}` · `country/:country` · `year/:year` · `quality/:slug` · `size/:slug` · `star/:slug` · `director/:slug` · `tvshows` · `tv-network/:slug` · `tv-year/:year` · `tv-cast/:slug`
**LK21** `/movie/api/lk21`: `homepage` · `filters` · `smart-search` · `search?q=` · `latest` · `popular` · `top-rated` · `latest-series` · `release` · `featured` · `genre/:genre` · `country/:country` · `year/:year` · `quality/:quality` · `detail/:slug` (stream) · `download/:slug`
**LK21 Drama** `/movie/api/lk21drama`: `homepage` · `filters` · `smart-search` · `search?q=` · `top-series-today` · `series/{ongoing,complete,west,asian}` · `latest` · `popular` · `top-rated` · `marathon` · `family` · `release` · `genre/:genre` · `country/:country` · `year/:year` · `detail/:slug` · `download/:slug`
**Movies (AquaAquaria)** `/movie/api/movies`: `home` · `ongoing` · `completed` · `latest` · `latest-movies` · `az-list?letter=` · `drama-{korea,china,thailand,jepang}` · `series-barat` · `netflix` · `film-{korea,china,barat}` · `variety-show` · `animasi` · `search?q=` · `detail/:slug` · `watch/:slug`
**Movies2 (AquaAquaria)** `/movie/api/movies2`: `home` · `movies` · `serial-tv` · `animation` · `hentai` · `semi` · `semi-indo` · `semi-barat` · `romance` · `best-rating` · `livestream` · `search?q=` · `genre/:genre` · `country/:country` · `year/:year` · `detail/:slug`
**FlixHQ** `/movie/api/flixhq` (English/intl, consumet-style HLS): `home` · `media/search?q=` · `media/suggestions?q=` · `media/filter?genre=&country=&type=&quality=&year=&page=` · `media/upcoming` · `movies/category/:category` · `tv/category/:category` · `media/:id` (info) · `media/:episodeId/servers` · `sources/:episodeId?server=vidcloud` (stream m3u8+subs) · `genres/:genre` · `countries/:country`
**HiMovies** `/movie/api/himovies` (English/intl): `home` · `media/search?q=` · `media/suggestions?q=` · `media/filter` · `media/upcoming` · `movies/category/:category` · `tv/category/:category` · `media/:id` · `media/:episodeId/servers` · `sources/:episodeId?server=megacloud` · `genres/:genre` · `countries/:country`
