// bot.js - Bot intelligente per il gioco della dama con minimax e alpha-beta pruning

// Restituisce la profondità di ricerca in base alla difficoltà selezionata
function getSearchDepth() {
  var diff = document.getElementById("botDifficulty").value;
  if (diff === "facile") return 2;   // Profondità minore per facilitare il principiante
  if (diff === "medio") return 4;
  if (diff === "difficile") return 6;
  return 2;
}

// Restituisce il ritardo (in ms) da applicare per l'animazione della cattura
function getCaptureDelay() {
  var diff = document.getElementById("botDifficulty").value;
  if (diff === "facile") return 1000;  // 1 secondo di delay
  if (diff === "medio") return 500;      // 0.5 secondi
  if (diff === "difficile") return 200;   // 0.2 secondi
  return 500;
}

// Valutazione della posizione: pedina = 1; dama = 1.5 (gli avversari contano negativamente)
function evaluateBoard(state) {
  var score = 0;
  for (var r = 0; r < 8; r++) {
    for (var c = 0; c < 8; c++) {
      if (state[r][c]) {
        var piece = state[r][c];
        var isKing = (piece === piece.toUpperCase());
        var pieceScore = isKing ? 1.5 : 1;
        score += (piece.toLowerCase() === "b") ? pieceScore : -pieceScore;
      }
    }
  }
  return score;
}

// Genera tutte le mosse legali per un determinato colore
function generateLegalMoves(color, state) {
  var moves = [];
  for (var r = 0; r < 8; r++) {
    for (var c = 0; c < 8; c++) {
      if (state[r][c] && state[r][c].toLowerCase() === color) {
        moves = moves.concat(getLegalMovesForPiece({ r: r, c: c }, state, color));
      }
    }
  }
  return moves;
}

// Funzione minimax con alpha-beta pruning
function minimax(state, depth, alpha, beta, maximizingPlayer) {
  var botColor = (window.playerColor === "w") ? "b" : "w";
  var currentColor = maximizingPlayer ? botColor : window.playerColor;
  if (depth === 0 || generateLegalMoves(currentColor, state).length === 0) {
    return { score: evaluateBoard(state) };
  }
  var legalMoves = [];
  for (var r = 0; r < 8; r++) {
    for (var c = 0; c < 8; c++) {
      if (state[r][c] && state[r][c].toLowerCase() === currentColor) {
        legalMoves = legalMoves.concat(getLegalMovesForPiece({ r: r, c: c }, state, currentColor));
      }
    }
  }
  if (maximizingPlayer) {
    var maxEval = -Infinity, bestMove = null;
    for (var move of legalMoves) {
      var newState = applyMoveState(state, move);
      var result = minimax(newState, depth - 1, alpha, beta, false);
      if (result.score > maxEval) {
        maxEval = result.score;
        bestMove = move;
      }
      alpha = Math.max(alpha, result.score);
      if (beta <= alpha) break;
    }
    return { move: bestMove, score: maxEval };
  } else {
    var minEval = Infinity, bestMove = null;
    for (var move of legalMoves) {
      var newState = applyMoveState(state, move);
      var result = minimax(newState, depth - 1, alpha, beta, true);
      if (result.score < minEval) {
        minEval = result.score;
        bestMove = move;
      }
      beta = Math.min(beta, result.score);
      if (beta <= alpha) break;
    }
    return { move: bestMove, score: minEval };
  }
}

// Applica una mossa a uno stato (clone) e restituisce il nuovo stato
function applyMoveState(state, move) {
  var newState = cloneState(state);
  newState[move.to.r][move.to.c] = newState[move.from.r][move.from.c];
  newState[move.from.r][move.from.c] = null;
  if (move.chain) {
    move.chain.forEach(function(m) {
      newState[m.capture.r][m.capture.c] = null;
    });
  }
  // Promozione: se una pedina raggiunge il bordo, diventa dama
  if (newState[move.to.r][move.to.c] === "w" && move.to.r === 0) newState[move.to.r][move.to.c] = "W";
  if (newState[move.to.r][move.to.c] === "b" && move.to.r === 7) newState[move.to.r][move.to.c] = "B";
  return newState;
}

function cloneState(state) {
  return state.map(function(row) { return row.slice(); });
}

// Bot: esegue il calcolo della mossa migliore tramite minimax
function makeBotMove() {
  var botColor = (window.playerColor === "w") ? "b" : "w";
  var depth = getSearchDepth();
  var legalMoves = generateLegalMoves(botColor, window.boardState);
  if (legalMoves.length === 0) return;
  var result = minimax(window.boardState, depth, -Infinity, Infinity, true);
  var best = result.move;
  if (!best) best = legalMoves[Math.floor(Math.random() * legalMoves.length)];
  // Se la mossa prevede cattura, ritarda per mostrare l'animazione
  if (best && best.chain) {
    setTimeout(function(){
      window.boardState = applyMoveState(window.boardState, best);
      moveHistory.push(cellName(best.from) + "-" + cellName(best.to));
      currentTurn = (currentTurn === "w") ? "b" : "w";
      renderBoard();
      updateHistory();
      updateStatus();
      if (gameOver()) {
        alert("Partita terminata! Vittoria di " + getWinner() + "!");
      }
    }, getCaptureDelay());
  } else {
    window.boardState = applyMoveState(window.boardState, best);
    moveHistory.push(cellName(best.from) + "-" + cellName(best.to));
    currentTurn = (currentTurn === "w") ? "b" : "w";
    renderBoard();
    updateHistory();
    updateStatus();
    if (gameOver()) {
      alert("Partita terminata! Vittoria di " + getWinner() + "!");
    }
  }
}

// Funzione di utilità per convertire una cella in notazione tipo "d3"
function cellName(cell) {
  var files = "abcdefgh";
  return files[cell.c] + (8 - cell.r);
}
