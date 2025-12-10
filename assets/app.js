document.addEventListener('DOMContentLoaded', function() {
  const body = document.body;
  const toggle = document.getElementById('dm-toggle');
  const prefer = localStorage.getItem('rp-dark');
  
  if (prefer === null) {

    body.classList.remove('rp-dark');
  } else if (prefer === '1') {
    body.classList.add('rp-dark');
  } else {
    body.classList.remove('rp-dark');
  }

  if (toggle) {
    toggle.addEventListener('click', () => {
      body.classList.toggle('rp-dark');
      localStorage.setItem('rp-dark', body.classList.contains('rp-dark') ? '1' : '0');
    });
  }

  const topSearch = document.getElementById('top-search');
  if (topSearch) {
    topSearch.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        const q = topSearch.value.trim();
        if (q.length) {
          window.location.href = `/sports/public/players.php?q=${encodeURIComponent(q)}`;
        }
      }
    });
  }

  const searchForm = document.getElementById('live-search-form');
  if (searchForm) {
    const tableBody = document.getElementById('live-search-body');
    const spinner = document.getElementById('live-search-spinner');

    async function doSearch(e) {
      e && e.preventDefault();
      const form = new FormData(searchForm);
      const params = new URLSearchParams();
      for (const [k,v] of form.entries()) if (v) params.append(k, v);

      spinner && (spinner.style.display = 'inline-block');
      try {
        const res = await fetch('/sports/public/api/search.php?' + params.toString());
        const json = await res.json();
        renderResults(json);
      } catch (err) {
        console.error(err);
      } finally {
        spinner && (spinner.style.display = 'none');
      }
    }

    function renderResults(json) {
      if (!tableBody) return;
      tableBody.innerHTML = '';
      const rows = json.data || [];
      if (!rows.length) {
        tableBody.innerHTML = '<tr><td colspan="10" class="text-muted text-center">No results</td></tr>';
        return;
      }
      for (const r of rows) {
        const tr = document.createElement('tr');
        tr.className = 'search-row';
        if (json.type === 'players') {
          tr.innerHTML = `<td>${escapeHtml(r.PlayerID)}</td>
                          <td>${escapeHtml(r.PlayerFname + ' ' + r.PlayerLname)}</td>
                          <td>${escapeHtml(r.TeamName)}</td>
                          <td><a class="btn btn-sm btn-outline-secondary" href="/sports/public/player_view.php?id=${encodeURIComponent(r.PlayerID)}">Open</a></td>`;
        } else if (json.type === 'tournaments') {
          tr.innerHTML = `<td>${escapeHtml(r.TournamentID)}</td>
                          <td>${escapeHtml(r.TournamentName)}</td>
                          <td>${escapeHtml(r.TournamentYear)}</td>
                          <td><a class="btn btn-sm btn-outline-secondary" href="/sports/public/tournament_view.php?id=${encodeURIComponent(r.TournamentID)}">Open</a></td>`;
        } else if (json.type === 'series') {
          tr.innerHTML = `<td>${escapeHtml(r.SeriesID)}</td>
                          <td>${escapeHtml(r.Team1Name || r.Team1ID)} vs ${escapeHtml(r.Team2Name || r.Team2ID)}</td>
                          <td>${escapeHtml(r.RoundType || '')}</td>
                          <td><a class="btn btn-sm btn-outline-secondary" href="/sports/public/series_view.php?id=${encodeURIComponent(r.SeriesID)}">Open</a></td>`;
        } else { // games
          tr.innerHTML = `<td>${escapeHtml(r.GameID)}</td>
                          <td>${escapeHtml(r.GameDate)}</td>
                          <td>${escapeHtml(r.Venue)}</td>
                          <td><a class="btn btn-sm btn-outline-secondary" href="/sports/public/game_view.php?id=${encodeURIComponent(r.GameID)}">Open</a></td>`;
        }
        tableBody.appendChild(tr);
      }
    }

    function escapeHtml(s) {
      if (s === null || s === undefined) return '';
      return String(s).replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;');
    }

    searchForm.addEventListener('input', doSearch);

    doSearch();
  }
});