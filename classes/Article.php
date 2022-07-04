<?php
require_once ("Dbh.php");

class Article extends Dbh
{
    public $id = null;
    public $publicationDate = null;
    public $title = null;
    public $summary = null;
    public $content = null;
    public function __construct($data = array())
    {
        if (isset($data['id'])) $this->id = (int)$data['id'];
        if (isset($data['publicationDate'])) $this->publicationDate = (int)$data['publicationDate'];
        if (isset($data['title'])) $this->title = preg_replace("/[^\.\,\-\_\'\"\@\?\!\:\$ a-zA-Z0-9()]/", "", $data['title']);
        if (isset($data['summary'])) $this->summary = preg_replace("/[^\.\,\-\_\'\"\@\?\!\:\$ a-zA-Z0-9()]/", "", $data['summary']);
        if (isset($data['content'])) $this->content = $data['content'];
    }


   

    public function storeFormValues($params)
    {

        $this->__construct($params);

        if (isset($params['publicationDate']))
        {
            $publicationDate = explode('-', $params['publicationDate']);

            if (count($publicationDate) == 3)
            {
                list ($y, $m, $d) = $publicationDate;
                $this->publicationDate = mktime(0, 0, 0, $m, $d, $y);
            }
        }
    }


    public function getById($id)
    {
        $sql = "SELECT *, UNIX_TIMESTAMP(publicationDate) AS publicationDate FROM articles WHERE id = ?";
        $st = $this->connect()->prepare($sql);
//        $st->bindValue(":id", $id, PDO::PARAM_INT);
        $st->execute([$id]);
        $row = $st->fetch();
        $st = null;
        if ($row) return new Article($row);
    }

    public function getList($numRows = 20)
    {
        $dbh = $this->connect();

        //$sql = "SELECT SQL_CALC_FOUND_ROWS *, UNIX_TIMESTAMP(publicationDate) AS publicationDate FROM articles ORDER BY articles.publicationDate DESC LIMIT ?";
        $sql = "SELECT SQL_CALC_FOUND_ROWS *, UNIX_TIMESTAMP(publicationDate) AS publicationDate FROM articles ORDER BY articles.publicationDate DESC LIMIT :numRows";

        $st = $dbh->prepare($sql);
        $st->bindValue(':numRows', $numRows, PDO::PARAM_INT);
        $st->execute();
        $list = array();

        $rows = $st->fetchAll();

        foreach ($rows as $row)
        {
            $article = new Article($row);
            $list[] = $article;
        }

        $totalRows = array();
        $sql = "SELECT FOUND_ROWS() AS totalRows";
        $totalRows = $dbh->query($sql)->fetch();
        return (array("results" => $list, "totalRows" => $totalRows['totalRows'])); // -> parce que FETCH_ASSOC -> Sinon Both retourne [0] aussi...
    }

    public function insert()
    {
        if (!is_null($this->id)) trigger_error("Article::insert(): Attempt to insert an Article object that already has its ID property set (to $this->id).", E_USER_ERROR);
        $sql = "INSERT INTO articles ( publicationDate, title, summary, content ) VALUES ( FROM_UNIXTIME(?), ?, ?, ?)";
        $st = $this->connect()->prepare($sql);
        $st->execute([$this->publicationDate, $this->title, $this->summary, $this->content]);
        $this->id = $this->connect()->lastInsertId();
    }

    public function update()
    {
        if (is_null($this->id)) trigger_error("Article::update(): Attempt to update an Article object that does not have its ID property set.", E_USER_ERROR);

        $sql = "UPDATE articles SET publicationDate=FROM_UNIXTIME(?), title = ?, summary = ?, content = ? WHERE id = ?";
        $st = $this->connect()->prepare($sql);
        $st->execute([$this->publicationDate, $this->title, $this->summary, $this->content, $this->id]);
    }

    public function delete()
    {
        if (is_null($this->id)) trigger_error("Article::delete(): Attempt to delete an Article object that does not have its ID property set.", E_USER_ERROR);
        $st = $this->connect()->prepare("DELETE FROM articles WHERE id = ? LIMIT 1");
        $st->execute([$this->id]);
    }
}
