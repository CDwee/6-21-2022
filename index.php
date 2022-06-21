<!-- Started at 11:04 6-21-2022 -->

<?php
$songQuery = mysqli_query($con, "SELECT id FROM songs ORDER BY RAND() LIMIT 10");

$resultArray = array();

while($row = mysqli_fetch_array($songQuery)) {
    array_push($resultArray, $row['id']);
}

$jsonArray = json_encode($resultArray);
?>

<script>

$(document).ready(function() {
    var newPlaylist = <?php echo $jsonArray; ?>;
    audioElement = new Audio();
    setTrack(newPlaylist[0], newPlaylist, false);
    updateVolumeProgressBar(audioElement.audio);


    $("#nowPlayingBarContainer").on("mousedown touchstart mousemove touchmove", function(e) {
        e.preventDefault();
    });


    $(".playbackBar .progressBar").mousedown(function() {
        mouseDown = true;
    });

    $(".playbackBar .progressBar").mousemove(function(e) {
        if(mouseDown == true) {
            //Set time of song, depending on position of mouse
            timeFromOffset(e, this);
        }
    });

    $(".playbackBar .progressBar").mouseup(function(e) {
        timeFromOffset(e, this);
    });


    $(".volumeBar .progressBar").mousedown(function() {
        mouseDown = true;
    });

    $(".volumeBar .progressBar").mousemove(function(e) {
        if(mouseDown == true) {

            var percentage = e.offsetX / $(this).width();

            if(percentage >= 0 && percentage <= 1) {
                audioElement.audio.volume = percentage;
            }
        }
    });

    $(".volumeBar .progressBar").mouseup(function(e) {
        var percentage = e.offsetX / $(this).width();

        if(percentage >= 0 && percentage <= 1) {
            audioElement.audio.volume = percentage;
        }
    });

    $(document).mouseup(function() {
        mouseDown = false;
    });




});

function timeFromOffset(mouse, progressBar) {
    var percentage = mouse.offsetX / $(progressBar).width() * 100;
    var seconds = audioElement.audio.duration * (percentage / 100);
    audioElement.setTime(seconds);
}

function prevSong() {
    if(audioElement.audio.currentTime >= 3 || currentIndex == 0) {
        audioElement.setTime(0);
    }
    else {
        currentIndex = currentIndex - 1;
        setTrack(currentPlaylist[currentIndex], currentPlaylist, true);
    }
}

function nextSong() {
    if(repeat == true) {
        audioElement.setTime(0);
        playSong();
        return;
    }

    if(currentIndex == currentPlaylist.length - 1) {
        currentIndex = 0;
    }
    else {
        currentIndex++;
    }

    var trackToPlay = shuffle ? shufflePlaylist[currentIndex] : currentPlaylist[currentIndex];
    setTrack(trackToPlay, currentPlaylist, true);
}

function setRepeat() {
    repeat = !repeat;
    var imageName = repeat ? "repeat-active.png" : "repeat.png";
    $(".controlButton.repeat img").attr("src", "assets/images/icons/" + imageName);
}

function setMute() {
    audioElement.audio.muted = !audioElement.audio.muted;
    var imageName = audioElement.audio.muted ? "volume-mute.png" : "volume.png";
    $(".controlButton.volume img").attr("src", "assets/images/icons/" + imageName);
}

function setShuffle() {
    shuffle = !shuffle;
    var imageName = shuffle ? "shuffle-active.png" : "shuffle.png";
    $(".controlButton.shuffle img").attr("src", "assets/images/icons/" + imageName);

    if(shuffle == true) {
        //Randomize playlist
        shuffleArray(shufflePlaylist);
        currentIndex = shufflePlaylist.indexOf(audioElement.currentlyPlaying.id);
    }
    else {
        //shuffle has been deactivated
        //go back to regular playlist
        currentIndex = currentPlaylist.indexOf(audioElement.currentlyPlaying.id);
    }

}

function shuffleArray(a) {
    var j, x, i;
    for (i = a.length; i; i--) {
        j = Math.floor(Math.random() * i);
        x = a[i - 1];
        a[i - 1] = a[j];
        a[j] = x;
    }
}


function setTrack(trackId, newPlaylist, play) {

    if(newPlaylist != currentPlaylist) {
        currentPlaylist = newPlaylist;
        shufflePlaylist = currentPlaylist.slice();
        shuffleArray(shufflePlaylist);
    }

    if(shuffle == true) {
        currentIndex = shufflePlaylist.indexOf(trackId);
    }
    else {
        currentIndex = currentPlaylist.indexOf(trackId);
    }
    pauseSong();

    $.post("includes/handlers/ajax/getSongJson.php", { songId: trackId }, function(data) {

        var track = JSON.parse(data);
        $(".trackName span").text(track.title);

        $.post("includes/handlers/ajax/getArtistJson.php", { artistId: track.artist }, function(data) {
            var artist = JSON.parse(data);
            $(".artistName span").text(artist.name);
        });

        $.post("includes/handlers/ajax/getAlbumJson.php", { albumId: track.album }, function(data) {
            var album = JSON.parse(data);
            $(".albumLink img").attr("src", album.artworkPath);
        });


        audioElement.setTrack(track);

        if(play == true) {
            playSong();
        }
    });
}

function playSong() {

    if(audioElement.audio.currentTime == 0) {
        $.post("includes/handlers/ajax/updatePlays.php", { songId: audioElement.currentlyPlaying.id });
    }

    $(".controlButton.play").hide();
    $(".controlButton.pause").show();
    audioElement.play();
}

function pauseSong() {
    $(".controlButton.play").show();
    $(".controlButton.pause").hide();
    audioElement.pause();
}
</script>


<div id="nowPlayingBarContainer">

    <div id="nowPlayingBar">

        <div id="nowPlayingLeft">
            <div class="content">
                <span class="albumLink">
                    <img src="" class="albumArtwork">
                </span>

                <div class="trackInfo">

                    <span class="trackName">
                        <span></span>
                    </span>

                    <span class="artistName">
                        <span></span>
                    </span>

                </div>



            </div>
        </div>

        <div id="nowPlayingCenter">

            <div class="content playerControls">

                <div class="buttons">

                    <button class="controlButton shuffle" title="Shuffle button" onclick="setShuffle()">
                        <img src="assets/images/icons/shuffle.png" alt="Shuffle">
                    </button>

                    <button class="controlButton previous" title="Previous button" onclick="prevSong()">
                        <img src="assets/images/icons/previous.png" alt="Previous">
                    </button>

                    <button class="controlButton play" title="Play button" onclick="playSong()">
                        <img src="assets/images/icons/play.png" alt="Play">
                    </button>

                    <button class="controlButton pause" title="Pause button" style="display: none;" onclick="pauseSong()">
                        <img src="assets/images/icons/pause.png" alt="Pause">
                    </button>

                    <button class="controlButton next" title="Next button" onclick="nextSong()">
                        <img src="assets/images/icons/next.png" alt="Next">
                    </button>

                    <button class="controlButton repeat" title="Repeat button" onclick="setRepeat()">
                        <img src="assets/images/icons/repeat.png" alt="Repeat">
                    </button>

                </div>


                <div class="playbackBar">

                    <span class="progressTime current">0.00</span>

                    <div class="progressBar">
                        <div class="progressBarBg">
                            <div class="progress"></div>
                        </div>
                    </div>

                    <span class="progressTime remaining">0.00</span>


                </div>


            </div>


        </div>

        <div id="nowPlayingRight">
            <div class="volumeBar">

                <button class="controlButton volume" title="Volume button" onclick="setMute()">
                    <img src="assets/images/icons/volume.png" alt="Volume">
                </button>

                <div class="progressBar">
                    <div class="progressBarBg">
                        <div class="progress"></div>
                    </div>
                </div>

            </div>
        </div>




    </div>

</div>

<?php include("includes/includedFiles.php"); 

if(isset($_GET['id'])) {
	$albumId = $_GET['id'];
}
else {
	header("Location: index.php");
}

$album = new Album($con, $albumId);
$artist = $album->getArtist();
?>

<div class="entityInfo">

	<div class="leftSection">
		<img src="<?php echo $album->getArtworkPath(); ?>">
	</div>

	<div class="rightSection">
		<h2><?php echo $album->getTitle(); ?></h2>
		<p>By <?php echo $artist->getName(); ?></p>
		<p><?php echo $album->getNumberOfSongs(); ?> songs</p>

	</div>

</div>


<div class="tracklistContainer">
	<ul class="tracklist">
		
		<?php
		$songIdArray = $album->getSongIds();

		$i = 1;
		foreach($songIdArray as $songId) {

			$albumSong = new Song($con, $songId);
			$albumArtist = $albumSong->getArtist();

			echo "<li class='tracklistRow'>
					<div class='trackCount'>
						<img class='play' src='assets/images/icons/play-white.png' onclick='setTrack(\"" . $albumSong->getId() . "\", tempPlaylist, true)'>
						<span class='trackNumber'>$i</span>
					</div>


					<div class='trackInfo'>
						<span class='trackName'>" . $albumSong->getTitle() . "</span>
						<span class='artistName'>" . $albumArtist->getName() . "</span>
					</div>

					<div class='trackOptions'>
						<img class='optionsButton' src='assets/images/icons/more.png'>
					</div>

					<div class='trackDuration'>
						<span class='duration'>" . $albumSong->getDuration() . "</span>
					</div>


				</li>";

			$i = $i + 1;
		}

		?>

		<script>
			var tempSongIds = '<?php echo json_encode($songIdArray); ?>';
			tempPlaylist = JSON.parse(tempSongIds);
		</script>

	</ul>
</div>

<?php
include("includes/config.php");
include("includes/classes/Artist.php");
include("includes/classes/Album.php");
include("includes/classes/Song.php");

//session_destroy(); LOGOUT

if(isset($_SESSION['userLoggedIn'])) {
    $userLoggedIn = $_SESSION['userLoggedIn'];
    echo "<script>userLoggedIn = '$userLoggedIn';</script>";
}
else {
    header("Location: register.php");
}

?>

<html>
<head>
    <title>Welcome to Slotify!</title>

    <link rel="stylesheet" type="text/css" href="assets/css/style.css">

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="assets/js/script.js"></script>
</head>

<body>

    <div id="mainContainer">

        <div id="topContainer">

            <?php include("includes/navBarContainer.php"); ?>

            <div id="mainViewContainer">

                <div id="mainContent">
                  
<div id="navBarContainer">
    <nav class="navBar">

        <span role="link" tabindex="0" onclick="openPage('index.php')" class="logo">
            <img src="assets/images/icons/logo.png">
        </span>


        <div class="group">

            <div class="navItem">
                <span role='link' tabindex='0' onclick='openPage("search.php")' class="navItemLink">
                    Search
                    <img src="assets/images/icons/search.png" class="icon" alt="Search">
                </span>
            </div>

        </div>

        <div class="group">
            <div class="navItem">
                <span role="link" tabindex="0" onclick="openPage('browse.php')" class="navItemLink">Browse</span>
            </div>

            <div class="navItem">
                <span role="link" tabindex="0" onclick="openPage('yourMusic.php')" class="navItemLink">Your Music</span>
            </div>

            <div class="navItem">
                <span role="link" tabindex="0" onclick="openPage('profile.php')" class="navItemLink">Reece Kenney</span>
            </div>
        </div>




    </nav>
</div>
                  
<?php
	class Song {

		private $con;
		private $id;
		private $mysqliData;
		private $title;
		private $artistId;
		private $albumId;
		private $genre;
		private $duration;
		private $path;

		public function __construct($con, $id) {
			$this->con = $con;
			$this->id = $id;

			$query = mysqli_query($this->con, "SELECT * FROM songs WHERE id='$this->id'");
			$this->mysqliData = mysqli_fetch_array($query);
			$this->title = $this->mysqliData['title'];
			$this->artistId = $this->mysqliData['artist'];
			$this->albumId = $this->mysqliData['album'];
			$this->genre = $this->mysqliData['genre'];
			$this->duration = $this->mysqliData['duration'];
			$this->path = $this->mysqliData['path'];
		}

		public function getTitle() {
			return $this->title;
		}

		public function getId() {
			return $this->id;
		}

		public function getArtist() {
			return new Artist($this->con, $this->artistId);
		}

		public function getAlbum() {
			return new Album($this->con, $this->albumId);
		}

		public function getPath() {
			return $this->path;
		}

		public function getDuration() {
			return $this->duration;
		}

		public function getMysqliData() {
			return $this->mysqliData;
		}

		public function getGenre() {
			return $this->genre;
		}

	}
?>
                  
var currentPlaylist = [];
var shufflePlaylist = [];
var tempPlaylist = [];
var audioElement;
var mouseDown = false;
var currentIndex = 0;
var repeat = false;
var shuffle = false;
var userLoggedIn;

function openPage(url) {

	if(url.indexOf("?") == -1) {
		url = url + "?";
	}

	var encodedUrl = encodeURI(url + "&userLoggedIn=" + userLoggedIn);
	console.log(encodedUrl);
	$("#mainContent").load(encodedUrl);
	$("body").scrollTop(0);
	history.pushState(null, null, url);
}


function formatTime(seconds) {
	var time = Math.round(seconds);
	var minutes = Math.floor(time / 60); //Rounds down
	var seconds = time - (minutes * 60);

	var extraZero = (seconds < 10) ? "0" : "";

	return minutes + ":" + extraZero + seconds;
}

function updateTimeProgressBar(audio) {
	$(".progressTime.current").text(formatTime(audio.currentTime));
	$(".progressTime.remaining").text(formatTime(audio.duration - audio.currentTime));

	var progress = audio.currentTime / audio.duration * 100;
	$(".playbackBar .progress").css("width", progress + "%");
}

function updateVolumeProgressBar(audio) {
	var volume = audio.volume * 100;
	$(".volumeBar .progress").css("width", volume + "%");
}

function Audio() {

	this.currentlyPlaying;
	this.audio = document.createElement('audio');

	this.audio.addEventListener("ended", function() {
		nextSong();
	});

	this.audio.addEventListener("canplay", function() {
		//'this' refers to the object that the event was called on
		var duration = formatTime(this.duration);
		$(".progressTime.remaining").text(duration);
	});

	this.audio.addEventListener("timeupdate", function(){
		if(this.duration) {
			updateTimeProgressBar(this);
		}
	});

	this.audio.addEventListener("volumechange", function() {
		updateVolumeProgressBar(this);
	});

	this.setTrack = function(track) {
		this.currentlyPlaying = track;
		this.audio.src = track.path;
	}

	this.play = function() {
		this.audio.play();
	}

	this.pause = function() {
		this.audio.pause();
	}

	this.setTime = function(seconds) {
		this.audio.currentTime = seconds;
	}

}
                                
<?php

if(isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
	include("includes/config.php");
	include("includes/classes/Artist.php");
	include("includes/classes/Album.php");
	include("includes/classes/Song.php");
}
else {
	include("includes/header.php");
	include("includes/footer.php");

	$url = $_SERVER['REQUEST_URI'];
	echo "<script>openPage('$url')</script>";
	exit();
}

?>
                                
<?php 
include("includes/includedFiles.php"); 
?>


<script>openPage("browse.php")</script>

<?php
include("includes/includedFiles.php");

if(isset($_GET['id'])) {
	$aristId = $_GET['id'];
}
else {
	header("Location: index.php");
}

$artist = new Artist($con, $aristId);
?>

<div class="entityInfo">

	<div class="centerSection">

		<div class="artistInfo">

			<h1 class="artistName"><?php echo $artist->getName(); ?></h1>

			<div class="headerButtons">
				<button class="button green">PLAY</button>
			</div>

		</div>

	</div>

</div>
              
html, body {
    padding: 0;
    margin: 0;
    height: 100%;
}

* {
    font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
    color: #fff;
    letter-spacing: 1px;
}

body {
    background-color: #181818;
    font-size: 14px;
    min-width: 720px;
}

#nowPlayingBarContainer {
    width: 100%;
    background-color: #282828;
    bottom: 0;
    position: fixed;
    min-width: 620px;
}

#nowPlayingBar {
    display: flex;
    height: 90px;
    padding: 16px;
    box-sizing: border-box;
}

#nowPlayingLeft,
#nowPlayingRight {
    width: 30%;
    min-width: 180px;
}

#nowPlayingRight {
    position: relative;
    margin-top: 16px;
}

#nowPlayingCenter {
    width: 40%;
    max-width: 700px;
    display: flex;
    flex-direction: column;
    align-items: center;
}

#nowPlayingBar .content {
    width: 100%;
    height: 57px;
}

.playerControls .buttons {
    margin: 0 auto;
    display: table;
}

.controlButton {
    background-color: transparent;
    border: none;
    vertical-align: middle;
}

.controlButton img {
    width: 20px;
    height: 20px;
}

.controlButton.play img,
.controlButton.pause img {
    width: 32px;
    height: 32px;
}

.controlButton:hover {
    cursor: pointer;
}

.progressTime {
    color: #a0a0a0;
    font-size: 11px;
    min-width: 40px;
    text-align: center;
}

.playbackBar {
    display: flex;
}

.progressBar {
    width: 100%;
    height: 12px;
    display: inline-flex;
    cursor: pointer;
}

.progressBarBg {
    background-color: #404040;
    height: 4px;
    width: 100%;
    border-radius: 2px;
}

.progress {
    background-color: #a0a0a0;
    height: 4px;
    width: 0;
    border-radius: 2px;
}

.playbackBar .progressBar {
    margin-top: 3px;
}

#nowPlayingLeft .albumArtwork {
    height: 100%;
    max-width: 57px;
    margin-right: 15px;
    float: left;
    background-size: cover;
}

#nowPlayingLeft .trackInfo {
    display: table;
}

#nowPlayingLeft .trackInfo .trackName {
    margin: 6px 0;
    display: inline-block;
    width: 100%;
}

#nowPlayingLeft .trackInfo .artistName span {
    font-size: 12px;
    color: #a0a0a0;
}

.volumeBar {
    width: 180px;
    position: absolute;
    right: 0;
}

.volumeBar .progressBar {
    width: 125px;
}

#topContainer {
    min-height: 100%;
    width: 100%;
}

#navBarContainer {
    background-color: #000;
    width: 220px;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
}

.navBar {
    padding: 25px;
    display: flex;
    flex-direction: column;
    -ms-flex-direction: column;
}

.logo {
    margin-bottom: 15px;
}

.logo img {
    width: 32px;
}

.navBar .group {
    border-top: 1px solid #a0a0a0;
    padding: 10px 0;
}

.navItem {
    padding: 10px 0;
    font-size: 14px;
    font-weight: 700;
    display: block;
    letter-spacing: 1px;
    position: relative;
}

.navItemLink {
    color: #a0a0a0;
    text-decoration: none;
}

.navItemLink:hover {
    color: #ffffff;
}

.navItemLink .icon {
    position: absolute;
    right: 0;
    top: 6px;
    width: 25px;
}

#mainViewContainer {
    margin-left: 220px;
    padding-bottom: 90px;
    width: calc(100% - 220px);
}

#mainContent {
    padding: 0 20px;
}

.pageHeadingBig {
    padding: 20px;
    text-align: center;
}

.gridViewItem {
    display: inline-block;
    margin-right: 20px;
    width: 29%;
    max-width: 200px;
    min-width: 150px;
    margin-bottom: 20px;
}

.gridViewItem img {
    width: 100%;
}

.gridViewInfo {
    font-weight: 300;
    text-align: center;
    padding: 5px 0;
    text-overflow: ellipsis;
    overflow: hidden;
    white-space: nowrap;
}

.gridViewItem a {
    text-decoration: none;
}

.entityInfo {
    padding: 40px 0 10px 0;
    display: inline-block;
    width: 100%;
}

.entityInfo .leftSection {
    width: 30%;
    float: left;
    max-width: 250px;
}

.entityInfo .leftSection img {
    width: 100%;
}

.entityInfo .rightSection {
    width: 70%;
    float: left;
    padding: 5px 10px 5px 40px;
    box-sizing: border-box;
}

.entityInfo .rightSection h2 {
    margin-top: 0px;
}

.entityInfo .rightSection p {
    color: #939393;
    font-weight: 200;
}

.tracklist {
    padding: 0;
}

.tracklistRow {
    height: 40px;
    padding: 15px 10px;
    list-style: none;
}

.tracklistRow span {
    color: #939393;
    font-weight: 200;
}

.tracklistRow:hover {
    background-color: #282828;
}

.tracklistRow .trackCount {
    width: 8%;
    float: left;
}

.tracklistRow .trackCount img {
    width: 20px;
    visibility: hidden;
    position: absolute;
    cursor: pointer;
}

.tracklistRow:hover .trackCount img {
    visibility: visible;
}

.tracklistRow .trackCount span {
    visibility: visible;
}

.tracklistRow:hover .trackCount span {
    visibility: hidden;
}

.tracklistRow .trackInfo {
    width: 75%;
    float: left;
}

.tracklistRow .trackInfo span {
    display: block;
}

.tracklistRow .trackOptions {
    width: 5%;
    float: left;
    text-align: right;
}

.tracklistRow .trackOptions img {
    width: 15px;
    visibility: hidden;
}

.tracklistRow:hover .trackOptions img {
    visibility: visible;
}

.tracklistRow .trackDuration {
    width: 12%;
    float: left;
    text-align: right;
}

.tracklistRow .trackInfo .trackName {
    color: #fff;
    margin-bottom: 7px;
}

.artistInfo {
    text-align: center;
}

.button {
    color: #fff;
    cursor: pointer;
    margin-bottom: 10px;
    background-color: transparent;
    font-weight: 500;
    letter-spacing: 2px;
    border: 2px solid #fff;
    border-radius: 500px;
    padding: 15px;
    min-width: 130px;
}

.button.green {
    background-color: #2ebd59;
    border-color: #2ebd59;
}
              
<?php 
include("includes/includedFiles.php"); 
?>

<h1 class="pageHeadingBig">You Might Also Like</h1>

<div class="gridViewContainer">

    <?php
        $albumQuery = mysqli_query($con, "SELECT * FROM albums ORDER BY RAND() LIMIT 10");

        while($row = mysqli_fetch_array($albumQuery)) {
            



            echo "<div class='gridViewItem'>
                    <span role='link' tabindex='0' onclick='openPage(\"album.php?id=" . $row['id'] . "\")'>
                        <img src='" . $row['artworkPath'] . "'>

                        <div class='gridViewInfo'>"
                            . $row['title'] .
                        "</div>
                    </span>

                </div>";



        }
    ?>

</div>
              
<?php
include("../../config.php");

if(isset($_POST['songId'])) {
	$songId = $_POST['songId'];

	$query = mysqli_query($con, "UPDATE songs SET plays = plays + 1 WHERE id='$songId'");
}
?>
              
<?php
include("../../config.php");

if(isset($_POST['albumId'])) {
	$albumId = $_POST['albumId'];

	$query = mysqli_query($con, "SELECT * FROM albums WHERE id='$albumId'");

	$resultArray = mysqli_fetch_array($query);

	echo json_encode($resultArray);
}
?>
              
<?php
include("../../config.php");

if(isset($_POST['artistId'])) {
	$artistId = $_POST['artistId'];

	$query = mysqli_query($con, "SELECT * FROM artists WHERE id='$artistId'");

	$resultArray = mysqli_fetch_array($query);

	echo json_encode($resultArray);
}
?>
              
<!-- Ended ay 6:05 6-21-2022 -->
