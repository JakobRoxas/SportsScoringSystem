<?php
// public/api/search.php - returns JSON for live search
require_once __DIR__ . '/../../config.php';
header('Content-Type: application/json; charset=utf-8');

$type = $_GET['type'] ?? 'players';
$q = trim((string)($_GET['q'] ?? ''));

$allowed = ['players','tournaments','series','games','suggestions'];
if (!in_array($type, $allowed)) $type = 'players';

$out = ['type'=>$type,'data'=>[]];

try {
  if ($type === 'players' || $type === 'suggestions') {
    $sql = "SELECT p.PlayerID, p.PlayerFname, p.PlayerLname, t.TeamName
            FROM Player p
            JOIN Team t ON p.TeamID = t.TeamID
            WHERE 1=1";
    $params = [];
    if ($q !== '') {
      $sql .= " AND (p.PlayerFname LIKE :q OR p.PlayerLname LIKE :q OR p.PlayerID LIKE :q)";
      $params[':q'] = "%$q%";
    }
    $sql .= " ORDER BY p.PlayerLname ASC LIMIT 200";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $out['data'] = $stmt->fetchAll();
  } elseif ($type === 'tournaments') {
    $sql = "SELECT TournamentID, TournamentName, TournamentYear FROM Tournament WHERE 1=1";
    $params = [];
    if ($q !== '') { $sql .= " AND TournamentName LIKE :q"; $params[':q']="%$q%"; }
    $sql .= " ORDER BY TournamentYear DESC LIMIT 200";
    $stmt = $pdo->prepare($sql); $stmt->execute($params);
    $out['data'] = $stmt->fetchAll();
  } elseif ($type === 'series') {
    $sql = "SELECT s.SeriesID, s.Team1ID, s.Team2ID, r.RoundType, t1.TeamName AS Team1Name, t2.TeamName AS Team2Name
            FROM Series s 
            JOIN Round r ON s.RoundID = r.RoundID
            JOIN Team t1 ON s.Team1ID = t1.TeamID
            JOIN Team t2 ON s.Team2ID = t2.TeamID
            WHERE 1=1";
    $params = [];
    if ($q !== '') { $sql .= " AND (t1.TeamName LIKE :q OR t2.TeamName LIKE :q OR r.RoundType LIKE :q)"; $params[':q']="%$q%"; }
    $sql .= " ORDER BY s.SeriesID LIMIT 200";
    $stmt = $pdo->prepare($sql); $stmt->execute($params);
    $out['data'] = $stmt->fetchAll();
  } else { // games
    $sql = "SELECT g.GameID, g.SeriesID, g.GameNumber, g.GameDate, g.Venue, g.WinnerTeamID, t.TeamName AS WinnerTeamName
            FROM Game g
            LEFT JOIN Team t ON g.WinnerTeamID = t.TeamID
            WHERE 1=1";
    $params = [];
    if ($q !== '') { $sql .= " AND (g.Venue LIKE :q OR t.TeamName LIKE :q)"; $params[':q']="%$q%"; }
    $sql .= " ORDER BY g.GameDate DESC LIMIT 200";
    $stmt = $pdo->prepare($sql); $stmt->execute($params);
    $out['data'] = $stmt->fetchAll();
  }

  echo json_encode($out);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(['error'=>'internal','msg'=>$e->getMessage()]);
}