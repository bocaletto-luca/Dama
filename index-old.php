<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dama WebApp Single Player</title>
  
  <!-- Bootstrap CSS per un design moderno -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
 <link rel="stylesheet" href="style.css">
</head>
<body>
  <!-- HEADER -->
  <header>
    <h1>Dama WebApp Single Player</h1>
    <p>Gioca a Dama con tutte le regole ufficiali, orologi, undo/reset e bot intelligente (Facile, Medio, Difficile)!</p>
    <div id="gameStatus"></div>
  </header>
  
  <!-- OROLOGI -->
  <div id="clocks">
    <span id="clockWhite">Bianco: 05:00</span> | <span id="clockBlack">Nero: 05:00</span>
  </div>
  
  <!-- SCACCHIERA -->
  <div id="board"></div>
  
  <!-- INFO PANELS -->
  <div id="infoPanels" class="container">
    <div class="row">
      <div class="col-md-6">
        <h5>Catture</h5>
        <div id="capturedPieces"></div>
      </div>
      <div class="col-md-6">
        <h5>Registro Mosse</h5>
        <div id="moveHistory"></div>
      </div>
    </div>
  </div>
  
  <!-- CONTROLLI -->
  <div id="controls" class="container">
    <div class="mb-3">
      <label for="playerColor" class="form-label">Scegli il tuo colore:</label>
      <select id="playerColor" class="form-select w-50 mx-auto">
        <option value="w" selected>Bianco</option>
        <option value="b">Nero</option>
      </select>
    </div>
    <div class="mb-3">
      <label for="botDifficulty" class="form-label">Difficoltà del Bot:</label>
      <select id="botDifficulty" class="form-select w-50 mx-auto">
        <option value="facile" selected>Facile</option>
        <option value="medio">Medio</option>
        <option value="difficile">Difficile</option>
      </select>
    </div>
    <div class="mb-3">
      <label for="gameTime" class="form-label">Tempo di Partita per Lato:</label>
      <select id="gameTime" class="form-select w-50 mx-auto">
        <option value="300" selected>5 minuti</option>
        <option value="600">10 minuti</option>
        <option value="900">15 minuti</option>
        <option value="1200">20 minuti</option>
      </select>
    </div>
    <div class="mb-3">
      <button id="undoMove" class="btn btn-warning me-2">Undo Mossa</button>
      <button id="resetGame" class="btn btn-secondary">Reset Partita</button>
    </div>
  </div>
  
  <!-- FOOTER -->
  <footer>
    <p>&copy; <?php echo date("Y"); ?> Bocaletto Luca | <a href="https://bocaletto-luca.github.io" target="_blank" style="color:#fff;">GitHub</a> • <a href="https://bocalettoluca.altervista.org" target="_blank" style="color:#fff;">Sito Ufficiale</a></p>
  </footer>
  
  <!-- LIBRERIE JS -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
    //#region Variabili Globali e Setup
    var boardState = [];  // Matrice 8x8: "w" o "b" per pedine; "W" o "B" per dame; null per casella vuota.
    var currentTurn = "w";  // "w" (Bianco) o "b" (Nero)
    var moveHistory = [];
    var capturedW = [], capturedB = [];
    
    // Orologi
    var defaultTime = parseInt(document.getElementById("gameTime").value);
    var whiteTime = defaultTime, blackTime = defaultTime;
    var clockInterval = null;
    
    // Selezioni
    var playerColor = document.getElementById("playerColor").value; // "w" o "b"
    var botDifficulty = document.getElementById("botDifficulty").value; // "facile", "medio", "difficile"
    //#endregion
    
    //#region Inizializzazione e Rendering della Scacchiera
    function initBoardState() {
      boardState = [];
      for (var r = 0; r < 8; r++) {
        var row = [];
        for (var c = 0; c < 8; c++) {
          row.push(null);
        }
        boardState.push(row);
      }
      // Posiziona pezzi neri sulle righe 0-2 (solo caselle scure)
      for (var r = 0; r < 3; r++) {
        for (var c = 0; c < 8; c++) {
          if ((r + c) % 2 === 1) boardState[r][c] = "b";
        }
      }
      // Posiziona pezzi bianchi sulle righe 5-7
      for (var r = 5; r < 8; r++) {
        for (var c = 0; c < 8; c++) {
          if ((r + c) % 2 === 1) boardState[r][c] = "w";
        }
      }
    }
    
    function renderBoard() {
      var boardDiv = document.getElementById("board");
      boardDiv.innerHTML = "";
      for (var r = 0; r < 8; r++) {
        for (var c = 0; c < 8; c++) {
          var cell = document.createElement("div");
          cell.classList.add("cell");
          cell.id = "cell_" + r + "_" + c;
          if ((r + c) % 2 === 0) {
            cell.classList.add("light");
          } else {
            cell.classList.add("dark");
            cell.style.cursor = "pointer";
            cell.addEventListener("click", (function(r, c) {
              return function() { handleCellClick(r, c); };
            })(r, c));
          }
          if (boardState[r][c]) {
            var piece = document.createElement("div");
            var p = boardState[r][c];
            piece.classList.add("piece", p.toLowerCase());
            if (p === p.toUpperCase()) {
              piece.classList.add("king");
              piece.textContent = "D";
            } else {
              piece.textContent = p.toUpperCase();
            }
            cell.appendChild(piece);
          }
          boardDiv.appendChild(cell);
        }
      }
    }
    //#endregion
    
    //#region Funzione per determinare il Vincitore alla Fine
    function getWinner() {
      // Quando il gioco è terminato, currentTurn è il giocatore che NON può muovere.
      // Il vincitore sarà l'altro.
      return (currentTurn === "w") ? "Nero" : "Bianco";
    }
    //#endregion
    
    //#region Gestione Mosse e Regole
    function cloneState(state) {
      return state.map(function(row) { return row.slice(); });
    }
    
    function getCaptureSequences(cell, state, color) {
      var sequences = [];
      var r = cell.r, c = cell.c;
      var piece = state[r][c];
      if (!piece) return sequences;
      var isKing = (piece === piece.toUpperCase());
      var directions = isKing ? [[-1,-1],[-1,1],[1,-1],[1,1]] : (color === "w" ? [[-1,-1],[-1,1]] : [[1,-1],[1,1]]);
      directions.forEach(function(dir) {
        var r1 = r + dir[0], c1 = c + dir[1];
        var r2 = r + 2 * dir[0], c2 = c + 2 * dir[1];
        if (r2 >= 0 && r2 < 8 && c2 >= 0 && c2 < 8 &&
            state[r1][c1] && state[r1][c1].toLowerCase() !== color && !state[r2][c2]) {
          var newState = cloneState(state);
          newState[r2][c2] = newState[r][c];
          newState[r][c] = null;
          newState[r1][c1] = null;
          // Promozione
          if (color === "w" && r2 === 0 && newState[r2][c2] === "w") newState[r2][c2] = "W";
          if (color === "b" && r2 === 7 && newState[r2][c2] === "b") newState[r2][c2] = "B";
          var further = getCaptureSequences({ r: r2, c: c2 }, newState, color);
          if (further.length === 0) {
            sequences.push([{ from: { r: r, c: c }, to: { r: r2, c: c2 }, capture: { r: r1, c: c1 } }]);
          } else {
            further.forEach(function(seq) {
              sequences.push([{ from: { r: r, c: c }, to: { r: r2, c: c2 }, capture: { r: r1, c: c1 } }].concat(seq));
            });
          }
        }
      });
      return sequences;
    }
    
    function getLegalMovesForPiece(cell, state, color) {
      var captureSeq = getCaptureSequences(cell, state, color);
      if (captureSeq.length > 0) {
        var maxLen = Math.max(...captureSeq.map(seq => seq.length));
        var bestSeqs = captureSeq.filter(seq => seq.length === maxLen);
        var moves = [];
        bestSeqs.forEach(function(seq) {
          moves.push({ from: cell, to: seq[seq.length - 1].to, chain: seq });
        });
        return moves;
      }
      var moves = [];
      var piece = state[cell.r][cell.c];
      if (!piece) return moves;
      var isKing = (piece === piece.toUpperCase());
      var directions = isKing ? [[-1,-1],[-1,1],[1,-1],[1,1]] : (color === "w" ? [[-1,-1],[-1,1]] : [[1,-1],[1,1]]);
      directions.forEach(function(dir) {
        var rn = cell.r + dir[0], cn = cell.c + dir[1];
        if (rn >= 0 && rn < 8 && cn >= 0 && cn < 8 && !state[rn][cn]) {
          moves.push({ from: cell, to: { r: rn, c: cn } });
        }
      });
      return moves;
    }
    
    var selectedCell = null;
    function handleCellClick(r, c) {
      if (gameOver()) return;
      if (currentTurn !== playerColor) return;
      if (selectedCell === null) {
        if (boardState[r][c] && boardState[r][c].toLowerCase() === playerColor) {
          selectedCell = { r: r, c: c };
          highlightCell(r, c, true);
          highlightMoveOptions(selectedCell);
        }
      } else {
        if (isValidDestination(selectedCell, { r: r, c: c })) {
          clearHighlights();
          executeMove(selectedCell, { r: r, c: c });
          selectedCell = null;
          renderBoard();
          updateHistory();
          updateStatus();
          stopClock();
          startClock();
          if (gameOver()) {
            alert("Partita terminata! Vittoria di " + getWinner() + "!");
          } else if (currentTurn !== playerColor) {
            setTimeout(function() { makeBotMove(); }, 500);
          }
        } else {
          clearHighlights();
          selectedCell = null;
          renderBoard();
        }
      }
    }
    
    function highlightCell(r, c, flag) {
      var cell = document.getElementById("cell_" + r + "_" + c);
      if (cell) cell.classList.toggle("highlight", flag);
    }
    
    function highlightMoveOptions(cell) {
      var moves = getLegalMovesForPiece(cell, boardState, currentTurn);
      moves.forEach(function(move) {
        highlightCell(move.to.r, move.to.c, true);
      });
    }
    
    function clearHighlights() {
      var cells = document.getElementsByClassName("cell");
      for (var i = 0; i < cells.length; i++) {
        cells[i].classList.remove("highlight");
      }
    }
    
    function isValidDestination(fromCell, toCell) {
      var moves = getLegalMovesForPiece(fromCell, boardState, currentTurn);
      for (var i = 0; i < moves.length; i++) {
        if (moves[i].to.r === toCell.r && moves[i].to.c === toCell.c) return true;
      }
      return false;
    }
    
    // Esegue la mossa scelta; se la mossa include catture in catena, esegue l'intera sequenza.
    function executeMove(fromCell, toCell) {
      var legal = getLegalMovesForPiece(fromCell, boardState, currentTurn);
      var chosen = null;
      for (var i = 0; i < legal.length; i++) {
        if (legal[i].to.r === toCell.r && legal[i].to.c === toCell.c) {
          chosen = legal[i];
          break;
        }
      }
      if (!chosen) return;
      boardState[toCell.r][toCell.c] = boardState[fromCell.r][fromCell.c];
      boardState[fromCell.r][fromCell.c] = null;
      if (chosen.chain) {
        chosen.chain.forEach(function(m) {
          boardState[m.capture.r][m.capture.c] = null;
        });
      }
      moveHistory.push(cellName(fromCell) + "-" + cellName(toCell));
      // Se sono presenti ulteriori catture obbligatorie per lo stesso pezzo, il turno resta invariato
      var further = getLegalMovesForPiece(toCell, boardState, currentTurn).filter(m => m.chain);
      if (!(chosen.chain && further.length > 0)) {
        currentTurn = (currentTurn === "w") ? "b" : "w";
      }
      // Promozione: se una pedina raggiunge il bordo, diventa dama
      if (boardState[toCell.r][toCell.c] === "w" && toCell.r === 0) boardState[toCell.r][toCell.c] = "W";
      if (boardState[toCell.r][toCell.c] === "b" && toCell.r === 7) boardState[toCell.r][toCell.c] = "B";
    }
    
    function cellName(cell) {
      var files = "abcdefgh";
      return files[cell.c] + (8 - cell.r);
    }
    //#endregion
    
    //#region Registro, Stato e Orologi
    function updateHistory() {
      document.getElementById("moveHistory").innerHTML = moveHistory.join("<br>");
    }
    
    function updateStatus() {
      document.getElementById("gameStatus").textContent = "Turno: " + (currentTurn === "w" ? "Bianco" : "Nero");
    }
    
    function gameOver() {
      for (var r = 0; r < 8; r++) {
        for (var c = 0; c < 8; c++) {
          if (boardState[r][c] && boardState[r][c].toLowerCase() === currentTurn) {
            if (getLegalMovesForPiece({ r: r, c: c }, boardState, currentTurn).length > 0) return false;
          }
        }
      }
      return true;
    }
    
    function updateClockDisplay() {
      function formatTime(s) {
        var m = Math.floor(s / 60), sec = s % 60;
        return (m < 10 ? "0" + m : m) + ":" + (sec < 10 ? "0" + sec : sec);
      }
      document.getElementById("clockWhite").textContent = "Bianco: " + formatTime(whiteTime);
      document.getElementById("clockBlack").textContent = "Nero: " + formatTime(blackTime);
    }
    
    function startClock() {
      stopClock();
      clockInterval = setInterval(() => {
        if (gameOver()) { stopClock(); return; }
        if (currentTurn === "w") {
          whiteTime--;
          if (whiteTime < 0) { stopClock(); alert("Tempo esaurito per Bianco! Bot vince!"); return; }
        } else {
          blackTime--;
          if (blackTime < 0) { stopClock(); alert("Tempo esaurito per Nero! Tu vinci!"); return; }
        }
        updateClockDisplay();
      }, 1000);
    }
    
    function stopClock() {
      clearInterval(clockInterval);
      clockInterval = null;
    }
    
    function resetClocks() {
      defaultTime = parseInt(document.getElementById("gameTime").value);
      whiteTime = defaultTime;
      blackTime = defaultTime;
      updateClockDisplay();
    }
    //#endregion
    
    //#region Undo e Reset
    function undoMove() {
      if (moveHistory.length === 0) return;
      moveHistory.pop();
      initBoardState();
      var tempHistory = moveHistory.slice();
      moveHistory = [];
      currentTurn = "w";
      for (var m of tempHistory) {
        var parts = m.split("-");
        var from = cellFromName(parts[0]);
        var to = cellFromName(parts[1]);
        boardState[to.r][to.c] = boardState[from.r][from.c];
        boardState[from.r][from.c] = null;
        moveHistory.push(m);
        currentTurn = (currentTurn === "w") ? "b" : "w";
      }
      renderBoard();
      updateHistory();
      updateStatus();
      resetClocks();
    }
    
    function cellFromName(name) {
      var files = "abcdefgh";
      return { r: 8 - parseInt(name[1]), c: files.indexOf(name[0]) };
    }
    
    function resetGame() {
      initBoardState();
      moveHistory = [];
      capturedW = [];
      capturedB = [];
      currentTurn = "w";
      renderBoard();
      updateHistory();
      updateStatus();
      resetClocks();
      if (currentTurn !== playerColor) { setTimeout(() => { makeBotMove(); }, 500); }
      stopClock();
      startClock();
    }
    //#endregion
    
    //#region Avvio e Listener
    document.addEventListener("DOMContentLoaded", () => {
      playerColor = document.getElementById("playerColor").value;
      initBoardState();
      renderBoard();
      updateHistory();
      updateStatus();
      resetClocks();
      startClock();
      if (currentTurn !== playerColor) {
        setTimeout(() => { makeBotMove(); }, 500);
      }
    });
    
    document.getElementById("resetGame").addEventListener("click", resetGame);
    document.getElementById("undoMove").addEventListener("click", undoMove);
    document.getElementById("gameTime").addEventListener("change", resetClocks);
    document.getElementById("playerColor").addEventListener("change", function() {
      playerColor = this.value;
      renderBoard();
    });
    //#endregion
  </script>
  
  <!-- Includi il file bot.js per il bot intelligente -->
  <script src="bot.js"></script>
  
</body>
</html>
