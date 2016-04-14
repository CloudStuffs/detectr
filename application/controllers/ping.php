<?php

/**
 * Ping controller
 *
 * @author Shreyansh Goel, Hemant Mann
 */
use Framework\RequestMethods as RequestMethods;
use Framework\Registry as Registry;
use Framework\ArrayMethods as ArrayMethods;

class Ping extends Admin {

    /**
     * @before _secure, memberLayout
     */
    public function create() {
        $this->seo(array("title" => "Ping | Create","view" => $this->getLayoutView()));
        $view = $this->getActionView();

        if (RequestMethods::post('title')) {
            $ping = Registry::get('MongoDB')->ping;
            $time = strtotime(date('d-m-Y H:i:s'));
            $mongo_date = new MongoDate($time);

            $url = RequestMethods::post('url', '');
            $regex = Shared\Markup::websiteRegex();
            if (!preg_match("/^$regex$/", $url)) {
                $view->set("success", "Invalid Url");
                return;
            }

            $record = $ping->findOne(array('user_id' => (int) $this->user->id, 'url' => $url));
            if ($record) {
                $view->set("success", "Ping already created! Go to <a href='/ping/edit/". $record['url'] ."'>Edit</a>");
                return;
            }

            $ping->insert(array(
                "user_id" => (int) $this->user->id,
                "title" => RequestMethods::post('title'),
                "url" => $url,
                "interval" => RequestMethods::post('interval'),
                "live" => 1,
                "created" => $mongo_date,
            ));

            $view->set('success', 'Ping Created Successfully');
        }
    }
	
    /**
     * @before _secure, memberLayout
     */
    public function edit() {
        $this->seo(array("title" => "Ping | Edit","view" => $this->getLayoutView()));
        $view = $this->getActionView();

        $url = RequestMethods::get("link");

        $ping = Registry::get('MongoDB')->ping;
        $search = ['url' => $url, 'user_id' => (int) $this->user->id];
        $record = $ping->findOne($search);
        if (!$record) {
            $this->redirect('/member/index');
        }

        if (RequestMethods::post('title')) {
            $ping->update($search, array(
                '$set' => array(
                    "title" => RequestMethods::post('title'),
                    "interval" => RequestMethods::post('interval')
                )
            ));
            $record = $ping->findOne($search);
            $view->set("success", "Updated!!");
        }
        $view->set('title', $record['title'])
            ->set('url', $record['url'])
            ->set('interval', $record['interval']);
    }

    /**
     * @before _secure, memberLayout
     */
    public function manage() {
        $this->seo(array("title" => "Ping | Manage","view" => $this->getLayoutView()));
        $view = $this->getActionView();

        $ping = Registry::get('MongoDB')->ping;

        $page = RequestMethods::get("page", 1); $limit = RequestMethods::get("limit", 30);
        $where = array('live' => 1, 'user_id' => (int) $this->user->id);
        $count = $ping->count($where);
        
        $records = $ping->find($where);
        $records->skip($limit * ($page - 1));
        $records->limit($limit);
        $records->sort(array('created' => -1));
        
        $result = [];
        foreach ($records as $r) {
            $result[] = $r;
        }

        $view->set('records', $result)
            ->set('page', $page)
            ->set('limit', $limit)
            ->set('count', $count);
    }

    /**
     * @before _secure, memberLayout
     */
    public function remove() {
        $mongo = Registry::get('MongoDB');

        $url = RequestMethods::get("link");
        $ping = $mongo->ping;
        $search = ['url' => $url, 'user_id' => (int) $this->user->id];
        $r = $ping->findOne($search);
        if (!$r) {
            $this->redirect('/ping/manage');
        }

        $ping_stats = $mongo->ping_stats;
        $ping_stats->remove(['ping_id' => $r['_id']]);
        $ping->remove($search, ['justOne' => true]);

        $this->redirect('/ping/manage');
    }

    /**
     * @before _secure, memberLayout
     */
    public function stats() {
        $this->seo(array("title" => "Ping | Stats","view" => $this->getLayoutView()));
        $view = $this->getActionView();

        $url = RequestMethods::get("link");
        $ping = Registry::get("MongoDB")->ping;
        $search = array('user_id' => (int) $this->user->id, 'url' => $url);
        $record = $ping->findOne($search);
        if (!$record) {
            $this->redirect("/404");
        }

        $end_date = RequestMethods::get("enddate", date("Y-m-d"));
        $start_date = RequestMethods::get("startdate", date("Y-m-d", strtotime($end_date."-7 day")));
        $start_time = strtotime($start_date); $end_time = strtotime($end_date);

        $ping_stats = Registry::get("MongoDB")->ping_stats;
        $records = $ping_stats->find(array(
            'ping_id' => $record['_id'],
            'created' => array(
                '$gte' => new \MongoDate($start_time),
                '$lte' => new \MongoDate($end_time)
            )
        ));

        $obj = array();
        foreach ($records as $r) {
            $obj[] = array('y' => date('Y-m-d H:i:s', $r['created']->sec), 'a' => $r['latency']);
        }
        $view->set('ping', ArrayMethods::toObject($record))
            ->set('label', 'Latency')
            ->set('data', ArrayMethods::toObject($obj));
    }
}
