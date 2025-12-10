<?php
// handle admin POST actions and provide redirects
require_once __DIR__ . '/../config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

function flash($msg, $level = 'success') {
    $_SESSION['flash'] = ['msg'=>$msg, 'level'=>$level];
}

function redirect($location = 'index.php') {
    header("Location: $location");
    exit;
}

function validateRequired($fields, $data) {
    foreach ($fields as $field) {
        if (empty(trim($data[$field] ?? ''))) {
            flash("Field '$field' is required.", 'danger');
            redirect();
        }
    }
}

function validateIDFormat($id, $type = 'ID') {
    if (!preg_match('/^[A-Z]{2}[0-9]{3}$/', $id)) {
        flash("$type must be in format: 2 letters followed by 3 digits (e.g., TM001)", 'danger');
        redirect();
    }
}

function checkDuplicate($pdo, $table, $column, $value, $type = 'Record') {
    $stmt = $pdo->prepare("SELECT $column FROM $table WHERE $column = ?");
    $stmt->execute([$value]);
    if ($stmt->fetch()) {
        flash("$type already exists. Please use a different ID.", 'danger');
        redirect();
    }
}

function checkExists($pdo, $table, $column, $value, $type = 'Record') {
    $stmt = $pdo->prepare("SELECT $column FROM $table WHERE $column = ?");
    $stmt->execute([$value]);
    if (!$stmt->fetch()) {
        flash("$type does not exist.", 'danger');
        redirect();
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    flash('Invalid request method', 'danger');
    redirect();
}

$action = $_POST['action'] ?? '';
try {
    // ADD TEAM
    if ($action === 'add_team') {
        validateRequired(['TeamID', 'TeamName', 'TeamCity', 'TeamConf'], $_POST);
        $teamID = trim($_POST['TeamID']);
        $teamName = trim($_POST['TeamName']);
        $teamCity = trim($_POST['TeamCity']);
        $teamConf = trim($_POST['TeamConf']);
        
        validateIDFormat($teamID, 'Team ID');
        
        if (!in_array($teamConf, ['EAST', 'WEST'])) {
            flash('Conference must be EAST or WEST', 'danger');
            redirect();
        }
        
        checkDuplicate($pdo, 'Team', 'TeamID', $teamID, 'Team ID');
        
        $stmt = $pdo->prepare("INSERT INTO Team (TeamID, TeamName, TeamCity, TeamConf) VALUES (?, ?, ?, ?)");
        $stmt->execute([$teamID, $teamName, $teamCity, $teamConf]);
        flash('Team added.');
        redirect();
    }

    // ADD PLAYER
    if ($action === 'add_player') {
        validateRequired(['PlayerID', 'PlayerFname', 'PlayerLname', 'TeamID'], $_POST);
        $playerID = trim($_POST['PlayerID']);
        $fname = trim($_POST['PlayerFname']);
        $lname = trim($_POST['PlayerLname']);
        $teamID = trim($_POST['TeamID']);
        
        validateIDFormat($playerID, 'Player ID');
        checkDuplicate($pdo, 'Player', 'PlayerID', $playerID, 'Player ID');
        checkExists($pdo, 'Team', 'TeamID', $teamID, 'Team');
        
        $stmt = $pdo->prepare("INSERT INTO Player (PlayerID, PlayerFname, PlayerLname, TeamID) VALUES (?, ?, ?, ?)");
        $stmt->execute([$playerID, $fname, $lname, $teamID]);
        flash('Player added.');
        redirect();
    }

    // ADD COACH
    if ($action === 'add_coach') {
        validateRequired(['CoachID', 'CoachFname', 'CoachLname', 'TeamID'], $_POST);
        $coachID = trim($_POST['CoachID']);
        $fname = trim($_POST['CoachFname']);
        $lname = trim($_POST['CoachLname']);
        $teamID = trim($_POST['TeamID']);
        
        validateIDFormat($coachID, 'Coach ID');
        checkDuplicate($pdo, 'Coach', 'CoachID', $coachID, 'Coach ID');
        checkExists($pdo, 'Team', 'TeamID', $teamID, 'Team');
        
        $stmt = $pdo->prepare("INSERT INTO Coach (CoachID, CoachFname, CoachLname, TeamID) VALUES (?, ?, ?, ?)");
        $stmt->execute([$coachID, $fname, $lname, $teamID]);
        flash('Coach added.');
        redirect();
    }

    // ADD TOURNAMENT
    if ($action === 'add_tournament') {
        validateRequired(['TournamentID', 'TournamentName', 'TournamentYear'], $_POST);
        $tournamentID = trim($_POST['TournamentID']);
        $tournamentName = trim($_POST['TournamentName']);
        $year = trim($_POST['TournamentYear']);
        
        validateIDFormat($tournamentID, 'Tournament ID');
        
        if (!is_numeric($year) || (int)$year < 1900 || (int)$year > 2100) {
            flash('Year must be a valid number between 1900 and 2100', 'danger');
            redirect();
        }
        
        checkDuplicate($pdo, 'Tournament', 'TournamentID', $tournamentID, 'Tournament ID');
        
        $stmt = $pdo->prepare("INSERT INTO Tournament (TournamentID, TournamentName, TournamentYear) VALUES (?, ?, ?)");
        $stmt->execute([$tournamentID, $tournamentName, (int)$year]);
        flash('Tournament added.');
        redirect();
    }

    // ADD TOURNAMENT TEAM
    if ($action === 'add_tournament_team') {
        validateRequired(['TournamentID', 'TeamID', 'Seed'], $_POST);
        $tournamentID = trim($_POST['TournamentID']);
        $teamID = trim($_POST['TeamID']);
        $seed = trim($_POST['Seed']);
        
        if (!is_numeric($seed) || (int)$seed < 1) {
            flash('Seed must be a positive number', 'danger');
            redirect();
        }
        
        checkExists($pdo, 'Tournament', 'TournamentID', $tournamentID, 'Tournament');
        checkExists($pdo, 'Team', 'TeamID', $teamID, 'Team');
        
        $check = $pdo->prepare("SELECT * FROM TournamentTeam WHERE TournamentID = ? AND TeamID = ?");
        $check->execute([$tournamentID, $teamID]);
        if ($check->fetch()) {
            flash('This team is already in this tournament.', 'danger');
            redirect();
        }
        
        $stmt = $pdo->prepare("INSERT INTO TournamentTeam (TournamentID, TeamID, Seed) VALUES (?, ?, ?)");
        $stmt->execute([$tournamentID, $teamID, (int)$seed]);
        flash('Team added to tournament.');
        redirect();
    }

    // ADD ROUND
    if ($action === 'add_round') {
        validateRequired(['RoundID', 'RoundType', 'RoundNumber', 'TournamentID'], $_POST);
        $roundID = trim($_POST['RoundID']);
        $roundType = trim($_POST['RoundType']);
        $roundNumber = trim($_POST['RoundNumber']);
        $tournamentID = trim($_POST['TournamentID']);
        
        validateIDFormat($roundID, 'Round ID');
        
        if (!in_array($roundType, ['PRELIMINARIES', 'SEMI-FINALS', 'FINALS'])) {
            flash('Round Type must be PRELIMINARIES, SEMI-FINALS, or FINALS', 'danger');
            redirect();
        }
        
        if (!is_numeric($roundNumber) || (int)$roundNumber < 1) {
            flash('Round Number must be a positive number', 'danger');
            redirect();
        }
        
        checkDuplicate($pdo, 'Round', 'RoundID', $roundID, 'Round ID');
        checkExists($pdo, 'Tournament', 'TournamentID', $tournamentID, 'Tournament');
        
        $stmt = $pdo->prepare("INSERT INTO Round (RoundID, RoundType, RoundNumber, TournamentID) VALUES (?, ?, ?, ?)");
        $stmt->execute([$roundID, $roundType, (int)$roundNumber, $tournamentID]);
        flash('Round added.');
        redirect();
    }

    // ADD SERIES
    if ($action === 'add_series') {
        validateRequired(['SeriesID', 'RoundID', 'Team1ID', 'Team2ID'], $_POST);
        $seriesID = trim($_POST['SeriesID']);
        $roundID = trim($_POST['RoundID']);
        $team1ID = trim($_POST['Team1ID']);
        $team2ID = trim($_POST['Team2ID']);
        
        validateIDFormat($seriesID, 'Series ID');
        
        if ($team1ID === $team2ID) {
            flash('Team 1 and Team 2 cannot be the same team.', 'danger');
            redirect();
        }
        
        checkDuplicate($pdo, 'Series', 'SeriesID', $seriesID, 'Series ID');
        checkExists($pdo, 'Round', 'RoundID', $roundID, 'Round');
        checkExists($pdo, 'Team', 'TeamID', $team1ID, 'Team 1');
        checkExists($pdo, 'Team', 'TeamID', $team2ID, 'Team 2');
        
        $stmt = $pdo->prepare("INSERT INTO Series (SeriesID, RoundID, Team1ID, Team2ID) VALUES (?, ?, ?, ?)");
        $stmt->execute([$seriesID, $roundID, $team1ID, $team2ID]);
        flash('Series added.');
        redirect();
    }

    // ADD GAME
    if ($action === 'add_game') {
        validateRequired(['GameID', 'SeriesID', 'GameNumber', 'GameDate', 'Venue'], $_POST);
        $gameID = trim($_POST['GameID']);
        $seriesID = trim($_POST['SeriesID']);
        $gameNumber = trim($_POST['GameNumber']);
        $gameDate = trim($_POST['GameDate']);
        $venue = trim($_POST['Venue']);
        
        validateIDFormat($gameID, 'Game ID');
        
        if (!is_numeric($gameNumber) || (int)$gameNumber < 1 || (int)$gameNumber > 7) {
            flash('Game Number must be between 1 and 7', 'danger');
            redirect();
        }
        
        $dateObj = DateTime::createFromFormat('Y-m-d', $gameDate);
        if (!$dateObj || $dateObj->format('Y-m-d') !== $gameDate) {
            flash('Invalid date format. Use YYYY-MM-DD', 'danger');
            redirect();
        }
        
        checkDuplicate($pdo, 'Game', 'GameID', $gameID, 'Game ID');
        checkExists($pdo, 'Series', 'SeriesID', $seriesID, 'Series');
        
        $stmt = $pdo->prepare("INSERT INTO Game (GameID, SeriesID, GameNumber, GameDate, Venue) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$gameID, $seriesID, (int)$gameNumber, $gameDate, $venue]);
        flash('Game added.');
        redirect();
    }

    // ADD SCORE
    if ($action === 'add_score') {
        validateRequired(['GameID', 'TeamID'], $_POST);
        $gameID = trim($_POST['GameID']);
        $teamID = trim($_POST['TeamID']);
        $points = trim($_POST['PointsScored'] ?? '');
        
        if ($points === '') {
            flash('Field \'PointsScored\' is required.', 'danger');
            redirect();
        }
        
        if (!is_numeric($points) || (int)$points < 0) {
            flash('Points must be a non-negative number', 'danger');
            redirect();
        }
        
        $check = $pdo->prepare("SELECT * FROM Score WHERE GameID = ? AND TeamID = ?");
        $check->execute([$gameID, $teamID]);
        if ($check->fetch()) {
            flash('Score already exists for this game and team.', 'danger');
            redirect();
        }
        
        checkExists($pdo, 'Game', 'GameID', $gameID, 'Game');
        checkExists($pdo, 'Team', 'TeamID', $teamID, 'Team');
        
        $stmt = $pdo->prepare("INSERT INTO Score (GameID, TeamID, PointsScored) VALUES (?, ?, ?)");
        $stmt->execute([$gameID, $teamID, (int)$points]);
        flash('Score added.');
        redirect();
    }

    // ADD PLAYER STAT
    if ($action === 'add_player_stat') {
        validateRequired(['GameID', 'PlayerID'], $_POST);
        $gameID = trim($_POST['GameID']);
        $playerID = trim($_POST['PlayerID']);
        $points = trim($_POST['Points'] ?? '');
        $rebounds = trim($_POST['Rebounds'] ?? '');
        $assists = trim($_POST['Assists'] ?? '');
        
        if ($points === '' || $rebounds === '' || $assists === '') {
            flash('All stat fields (Points, Rebounds, Assists) are required.', 'danger');
            redirect();
        }
        
        if (!is_numeric($points) || (int)$points < 0) {
            flash('Points must be a non-negative number', 'danger');
            redirect();
        }
        if (!is_numeric($rebounds) || (int)$rebounds < 0) {
            flash('Rebounds must be a non-negative number', 'danger');
            redirect();
        }
        if (!is_numeric($assists) || (int)$assists < 0) {
            flash('Assists must be a non-negative number', 'danger');
            redirect();
        }
        
        checkExists($pdo, 'Game', 'GameID', $gameID, 'Game');
        checkExists($pdo, 'Player', 'PlayerID', $playerID, 'Player');
        
        $del = $pdo->prepare("DELETE FROM PlayerStat WHERE GameID = ? AND PlayerID = ?");
        $del->execute([$gameID, $playerID]);

        $ins = $pdo->prepare("INSERT INTO PlayerStat (GameID, PlayerID, Points, Rebounds, Assists) VALUES (?, ?, ?, ?, ?)");
        $ins->execute([$gameID, $playerID, (int)$points, (int)$rebounds, (int)$assists]);

        flash('Player stats added.');
        redirect();
    }

    // SET WINNER
    if ($action === 'set_winner') {
        validateRequired(['SeriesID'], $_POST);
        $seriesID = trim($_POST['SeriesID']);
        $winnerID = !empty($_POST['WinnerTeamID']) ? trim($_POST['WinnerTeamID']) : null;
        
        $check = $pdo->prepare("SELECT SeriesID, Team1ID, Team2ID FROM Series WHERE SeriesID = ?");
        $check->execute([$seriesID]);
        $series = $check->fetch();
        
        if (!$series) {
            flash('Series does not exist.', 'danger');
            redirect('manage.php?type=series');
        }
        
        if ($winnerID && !in_array($winnerID, [$series['Team1ID'], $series['Team2ID']])) {
            flash('Winner must be one of the teams in this series.', 'danger');
            redirect('manage.php?type=series');
        }
        
        $stmt = $pdo->prepare("UPDATE Series SET WinnerTeamID = ? WHERE SeriesID = ?");
        $stmt->execute([$winnerID, $seriesID]);
        flash($winnerID ? 'Series winner updated.' : 'Series winner cleared.');
        redirect('manage.php?type=series');
    }

    // EDIT TEAM
    if ($action === 'edit_team') {
        validateRequired(['TeamID', 'TeamName', 'TeamCity', 'TeamConf'], $_POST);
        $teamID = trim($_POST['TeamID']);
        $teamName = trim($_POST['TeamName']);
        $teamCity = trim($_POST['TeamCity']);
        $teamConf = trim($_POST['TeamConf']);
        
        checkExists($pdo, 'Team', 'TeamID', $teamID, 'Team');
        
        if (!in_array($teamConf, ['EAST', 'WEST'])) {
            flash('Conference must be EAST or WEST', 'danger');
            redirect('manage.php?type=team');
        }
        
        $stmt = $pdo->prepare("UPDATE Team SET TeamName = ?, TeamCity = ?, TeamConf = ? WHERE TeamID = ?");
        $stmt->execute([$teamName, $teamCity, $teamConf, $teamID]);
        flash('Team updated.');
        redirect('manage.php?type=team');
    }

    // EDIT PLAYER
    if ($action === 'edit_player') {
        validateRequired(['PlayerID', 'PlayerFname', 'PlayerLname', 'TeamID'], $_POST);
        checkExists($pdo, 'Player', 'PlayerID', trim($_POST['PlayerID']), 'Player');
        checkExists($pdo, 'Team', 'TeamID', trim($_POST['TeamID']), 'Team');
        
        $stmt = $pdo->prepare("UPDATE Player SET PlayerFname = ?, PlayerLname = ?, TeamID = ? WHERE PlayerID = ?");
        $stmt->execute([trim($_POST['PlayerFname']), trim($_POST['PlayerLname']), trim($_POST['TeamID']), trim($_POST['PlayerID'])]);
        flash('Player updated.');
        redirect('manage.php?type=player');
    }

    // EDIT COACH
    if ($action === 'edit_coach') {
        validateRequired(['CoachID', 'CoachFname', 'CoachLname', 'TeamID'], $_POST);
        checkExists($pdo, 'Coach', 'CoachID', trim($_POST['CoachID']), 'Coach');
        checkExists($pdo, 'Team', 'TeamID', trim($_POST['TeamID']), 'Team');
        
        $stmt = $pdo->prepare("UPDATE Coach SET CoachFname = ?, CoachLname = ?, TeamID = ? WHERE CoachID = ?");
        $stmt->execute([trim($_POST['CoachFname']), trim($_POST['CoachLname']), trim($_POST['TeamID']), trim($_POST['CoachID'])]);
        flash('Coach updated.');
        redirect('manage.php?type=coach');
    }

    // EDIT TOURNAMENT
    if ($action === 'edit_tournament') {
        validateRequired(['TournamentID', 'TournamentName', 'TournamentYear'], $_POST);
        checkExists($pdo, 'Tournament', 'TournamentID', trim($_POST['TournamentID']), 'Tournament');
        
        $stmt = $pdo->prepare("UPDATE Tournament SET TournamentName = ?, TournamentYear = ? WHERE TournamentID = ?");
        $stmt->execute([trim($_POST['TournamentName']), (int)$_POST['TournamentYear'], trim($_POST['TournamentID'])]);
        flash('Tournament updated.');
        redirect('manage.php?type=tournament');
    }

    // EDIT ROUND
    if ($action === 'edit_round') {
        validateRequired(['RoundID', 'RoundType', 'RoundNumber', 'TournamentID'], $_POST);
        checkExists($pdo, 'Round', 'RoundID', trim($_POST['RoundID']), 'Round');
        checkExists($pdo, 'Tournament', 'TournamentID', trim($_POST['TournamentID']), 'Tournament');
        
        $stmt = $pdo->prepare("UPDATE Round SET RoundType = ?, RoundNumber = ?, TournamentID = ? WHERE RoundID = ?");
        $stmt->execute([trim($_POST['RoundType']), (int)$_POST['RoundNumber'], trim($_POST['TournamentID']), trim($_POST['RoundID'])]);
        flash('Round updated.');
        redirect('manage.php?type=round');
    }

    // EDIT SERIES
    if ($action === 'edit_series') {
        validateRequired(['SeriesID', 'RoundID', 'Team1ID', 'Team2ID'], $_POST);
        checkExists($pdo, 'Series', 'SeriesID', trim($_POST['SeriesID']), 'Series');
        checkExists($pdo, 'Round', 'RoundID', trim($_POST['RoundID']), 'Round');
        checkExists($pdo, 'Team', 'TeamID', trim($_POST['Team1ID']), 'Team 1');
        checkExists($pdo, 'Team', 'TeamID', trim($_POST['Team2ID']), 'Team 2');
        
        $winnerID = !empty($_POST['WinnerTeamID']) ? trim($_POST['WinnerTeamID']) : null;
        $stmt = $pdo->prepare("UPDATE Series SET RoundID = ?, Team1ID = ?, Team2ID = ?, WinnerTeamID = ? WHERE SeriesID = ?");
        $stmt->execute([trim($_POST['RoundID']), trim($_POST['Team1ID']), trim($_POST['Team2ID']), $winnerID, trim($_POST['SeriesID'])]);
        flash('Series updated.');
        redirect('manage.php?type=series');
    }

    // EDIT GAME
    if ($action === 'edit_game') {
        validateRequired(['GameID', 'SeriesID', 'GameNumber', 'GameDate', 'Venue'], $_POST);
        checkExists($pdo, 'Game', 'GameID', trim($_POST['GameID']), 'Game');
        checkExists($pdo, 'Series', 'SeriesID', trim($_POST['SeriesID']), 'Series');
        
        $winnerID = !empty($_POST['WinnerTeamID']) ? trim($_POST['WinnerTeamID']) : null;
        $stmt = $pdo->prepare("UPDATE Game SET SeriesID = ?, GameNumber = ?, GameDate = ?, Venue = ?, WinnerTeamID = ? WHERE GameID = ?");
        $stmt->execute([trim($_POST['SeriesID']), (int)$_POST['GameNumber'], trim($_POST['GameDate']), trim($_POST['Venue']), $winnerID, trim($_POST['GameID'])]);
        flash('Game updated.');
        redirect('manage.php?type=game');
    }

    // EDIT SCORE
    if ($action === 'edit_score') {
        validateRequired(['GameID', 'TeamID', 'PointsScored'], $_POST);
        
        $stmt = $pdo->prepare("UPDATE Score SET PointsScored = ? WHERE GameID = ? AND TeamID = ?");
        $stmt->execute([(int)$_POST['PointsScored'], trim($_POST['GameID']), trim($_POST['TeamID'])]);
        flash('Score updated.');
        redirect('manage.php?type=score');
    }

    // DELETE TEAM
    if ($action === 'delete_team') {
        validateRequired(['id'], $_POST);
        $teamID = trim($_POST['id']);
        checkExists($pdo, 'Team', 'TeamID', $teamID, 'Team');
        
        $stmt = $pdo->prepare("DELETE FROM Team WHERE TeamID = ?");
        $stmt->execute([$teamID]);
        flash('Team deleted.', 'warning');
        redirect('manage.php?type=team');
    }

    // DELETE PLAYER
    if ($action === 'delete_player') {
        validateRequired(['id'], $_POST);
        $playerID = trim($_POST['id']);
        checkExists($pdo, 'Player', 'PlayerID', $playerID, 'Player');
        
        $stmt = $pdo->prepare("DELETE FROM Player WHERE PlayerID = ?");
        $stmt->execute([$playerID]);
        flash('Player deleted.', 'warning');
        redirect('manage.php?type=player');
    }

    // DELETE COACH
    if ($action === 'delete_coach') {
        validateRequired(['id'], $_POST);
        $coachID = trim($_POST['id']);
        checkExists($pdo, 'Coach', 'CoachID', $coachID, 'Coach');
        
        $stmt = $pdo->prepare("DELETE FROM Coach WHERE CoachID = ?");
        $stmt->execute([$coachID]);
        flash('Coach deleted.', 'warning');
        redirect('manage.php?type=coach');
    }

    // DELETE TOURNAMENT
    if ($action === 'delete_tournament') {
        validateRequired(['id'], $_POST);
        $stmt = $pdo->prepare("DELETE FROM Tournament WHERE TournamentID = ?");
        $stmt->execute([trim($_POST['id'])]);
        flash('Tournament deleted.', 'warning');
        redirect('manage.php?type=tournament');
    }

    // DELETE ROUND
    if ($action === 'delete_round') {
        validateRequired(['id'], $_POST);
        $stmt = $pdo->prepare("DELETE FROM Round WHERE RoundID = ?");
        $stmt->execute([trim($_POST['id'])]);
        flash('Round deleted.', 'warning');
        redirect('manage.php?type=round');
    }

    // DELETE SERIES
    if ($action === 'delete_series') {
        validateRequired(['id'], $_POST);
        $stmt = $pdo->prepare("DELETE FROM Series WHERE SeriesID = ?");
        $stmt->execute([trim($_POST['id'])]);
        flash('Series deleted.', 'warning');
        redirect('manage.php?type=series');
    }

    // DELETE GAME
    if ($action === 'delete_game') {
        validateRequired(['id'], $_POST);
        $stmt = $pdo->prepare("DELETE FROM Game WHERE GameID = ?");
        $stmt->execute([trim($_POST['id'])]);
        flash('Game deleted.', 'warning');
        redirect('manage.php?type=game');
    }

    // DELETE SCORE
    if ($action === 'delete_score') {
        validateRequired(['gameID', 'teamID'], $_POST);
        $stmt = $pdo->prepare("DELETE FROM Score WHERE GameID = ? AND TeamID = ?");
        $stmt->execute([trim($_POST['gameID']), trim($_POST['teamID'])]);
        flash('Score deleted.', 'warning');
        redirect('manage.php?type=score');
    }

    flash('Unknown action.', 'danger');
    redirect();

} catch (Exception $e) {
    flash('Error: ' . $e->getMessage(), 'danger');
    redirect();
}

function recompute_game_and_series(PDO $pdo, string $gameID) {
    // fetch team scores for the game
    $stmt = $pdo->prepare("SELECT TeamID, PointsScored FROM Score WHERE GameID = ? ORDER BY TeamID ASC");
    $stmt->execute([$gameID]);
    $scores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $teams = [];
    foreach ($scores as $s) $teams[$s['TeamID']] = (int)$s['PointsScored'];
    if (count($teams) < 2) return;

    arsort($teams);
    $ordered = array_keys($teams);
    $top = $ordered[0];
    $second = $ordered[1] ?? null;
    $top_pts = $teams[$top];
    $second_pts = $second ? $teams[$second] : -1;

    if ($top_pts === $second_pts) {
        // tie: do not set winner
        return;
    }

    $u = $pdo->prepare("UPDATE Game SET WinnerTeamID = ? WHERE GameID = ?");
    $u->execute([$top, $gameID]);

    $s = $pdo->prepare("SELECT SeriesID FROM Game WHERE GameID = ?");
    $s->execute([$gameID]);
    $row = $s->fetch();
    if (!$row) return;
    $seriesID = $row['SeriesID'];

    $r = $pdo->prepare("SELECT Team1ID, Team2ID FROM Series WHERE SeriesID = ?");
    $r->execute([$seriesID]);
    $teamsRow = $r->fetch();
    if (!$teamsRow) return;
    $t1 = $teamsRow['Team1ID'];
    $t2 = $teamsRow['Team2ID'];

    $cstmt = $pdo->prepare("SELECT WinnerTeamID, COUNT(*) AS wins FROM Game WHERE SeriesID = ? AND WinnerTeamID IS NOT NULL GROUP BY WinnerTeamID");
    $cstmt->execute([$seriesID]);
    $counts = [];
    foreach ($cstmt->fetchAll(PDO::FETCH_ASSOC) as $c) $counts[$c['WinnerTeamID']] = (int)$c['wins'];

    $w1 = $counts[$t1] ?? 0;
    $w2 = $counts[$t2] ?? 0;

    if ($w1 >= 4) {
        $pdo->prepare("UPDATE Series SET WinnerTeamID = ? WHERE SeriesID = ?")->execute([$t1, $seriesID]);
    } elseif ($w2 >= 4) {
        $pdo->prepare("UPDATE Series SET WinnerTeamID = ? WHERE SeriesID = ?")->execute([$t2, $seriesID]);
    } else {
        $pdo->prepare("UPDATE Series SET WinnerTeamID = NULL WHERE SeriesID = ?")->execute([$seriesID]);
    }
}