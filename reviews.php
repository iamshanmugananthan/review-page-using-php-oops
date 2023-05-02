<?php
class ReviewSystem {
    private $mysqli; //private property for class that it holds the MySQLi object
    public function __construct($host, $user, $pass, $database) {
        $this->mysqli = new mysqli($host, $user, $pass, $database);
        if ($this->mysqli->connect_error) {
            exit('Failed to connect to database!'); 
        }
    }
    //class to retrieves reviews from the prakasoft database.
    public function getReviews($page_id) {
        $stmt = $this->mysqli->prepare('SELECT * FROM reviews WHERE page_id = ? ORDER BY submit_date DESC');
        $stmt->bind_param('i', $page_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $reviews = [];
        while ($row = $result->fetch_assoc()) {
            $reviews[] = $row;
        }
        return $reviews;
    }
    public function getReviewInfo($page_id) {
        $result = $this->mysqli->query("SELECT AVG(rating) AS overall_rating, COUNT(*) AS total_reviews FROM reviews WHERE page_id = $page_id");
        $review_info = $result->fetch_assoc();
        return $review_info;
    }
    public function addReview($page_id, $name, $rating, $content) {
        $stmt = $this->mysqli->prepare('INSERT INTO reviews (page_id, name, content, rating, submit_date) VALUES (?,?,?,?,NOW())');
        $stmt->bind_param('issi', $page_id, $name, $content, $rating);
        $stmt->execute();
        return 'Your review has been submitted!';
    }
    public function getTimeElapsedString($datetime, $full = false) {
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);
        $w = floor($diff->d / 7);
        $diff->d -= $w * 7;
        $string = ['y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second'];
        foreach ($string as $k => &$v) {
            if ($k == 'w' && $w) {
                $v = $w . ' week' . ($w > 1 ? 's' : '');
            } else if (isset($diff->$k) && $diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }
        if (!$full) $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . ' ago' : 'just now';
    }
}

if (isset($_GET['page_id'])) {
    $reviewSystem = new ReviewSystem('localhost', 'root', '', 'phpreviews');

    if (isset($_POST['name'], $_POST['rating'], $_POST['content'])) {
        $result = $reviewSystem->addReview($_GET['page_id'], $_POST['name'], $_POST['rating'], $_POST['content']);
        exit($result);
    }

    $reviews = $reviewSystem->getReviews($_GET['page_id']);
    $reviews_info = $reviewSystem->getReviewInfo($_GET['page_id']);
} else {
    exit('Please provide the page ID.');
}
?>

<div class="overall_rating">
    <span class="num"><?=number_format($reviews_info['overall_rating'], 1)?></span>
    <span class="stars"><?=str_repeat('&#9733;', round($reviews_info['overall_rating']))?></span>
    <span class="total"><?=$reviews_info['total_reviews']?> reviews</span>
</div>
<a href="#" class="write_review_btn">Write Review</a>
<div class="write_review">
    <form>
        <input name="name" type="text" placeholder="Your Name" required>
        <input name="rating" type="number" min="1" max="5" placeholder="Rating (1-5)" required>
        <textarea name="content" placeholder="Write your review here..." required></textarea>
        <button type="submit">Submit Review</button>
    </form>
</div>
<?php foreach ($reviews as $review): ?>
<div class="review">
    <h3 class="name"><?=htmlspecialchars($review['name'], ENT_QUOTES)?></h3>
    <div>
        <span class="rating"><?=str_repeat('&#9733;', $review['rating'])?></span>
        <span class="date"><?=$reviewSystem->getTimeElapsedString($review['submit_date'])?></span>
    </div>
    <p class="content"><?=htmlspecialchars($review['content'], ENT_QUOTES)?></p>
</div>
<?php endforeach ?>
