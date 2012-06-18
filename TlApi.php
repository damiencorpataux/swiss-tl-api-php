<?php

class TlApi {

    var $params = array(
    );

    var $meta = array();

    var $timer = null;

    function __construct($params=array()) {
        $this->params = $params;
    }

    protected function timer_start() {
        $this->timer = microtime();
    }
    protected function timer_lapse() {
        $end = microtime();
        list($start_usec, $start_sec) = explode(" ", $this->timer);
        list($end_usec, $end_sec) = explode(" ", $end);
        $diff_sec= intval($end_sec) - intval($start_sec);
        $diff_usec= floatval($end_usec) - floatval($start_usec);
        return floatval( $diff_sec ) + $diff_usec;
    }

    function get() {
        $this->timer_start();
        // Creates provider
        $provider = @$this->params['provider'];
        $providerClass = "Tl{$provider}Provider";
        if (!$provider || !class_exists($providerClass)) {
            throw new Exception(implode('', array(
                $provider ? 'Invalid' : 'Missing',
                " 'provider' parameter",
                $provider ? " ('{$provider}'). " : '. ',
                ' Valid providers are: ',
                implode(', ', $this->list_providers()).'.'
            )));
        }
        $provider = new $providerClass($this->params);
        // Creates data
        $response['data'] = $provider->get();
        // Creates meta
        $response['meta'] = array_merge(
            $this->meta,
            array('runtime' => $this->timer_lapse())
        );
        // Returns response
        return json_encode($response);
    }
    
    function list_providers() {
        $classes = get_declared_classes();
        $classes = array_filter($classes, function($name) { return preg_match('/^Tl\w+Provider$/i', $name); });
        $providers = array_map(function($name) { return preg_replace('/^Tl(\w+)Provider$/i', '$1', $name); }, $classes);
        $providers = array_map('strtolower', $providers);
        return $providers;
    }
}

/**
 * Data Provider base Class.
 * Used for providing various of data types, names resources in REST terminology.
 * Resources examples: Lines, Stations, Departures, ...
 */
abstract class TlProvider {

    /**
     * Used for caching result if queried more than once
     * Structure:
     * array(serialized_params => resulting_data_structure)
     */
    static $cache = array();

    var $params = array();

    /**
     * Constructor.
     * @param TlApi The calling TlApi instance.
     */
    function __construct($params=array()) {
        $this->params = $params;
        $this->init();
    }

    function init() {}

    function get() {
        if (!is_array(self::$cache)) return $this->get_data();
        ksort($this->params);
        $hash = sha1(serialize($this->params));
        $cache = &self::$cache[get_class($this)][$hash];
        return $cache ?
            $cache :
            $cache = $this->get_data();
    }

    protected function fetch_url($url) {
        $this->api->meta['sources'][] = $url;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_ENCODING, 'utf-8');
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    /**
     * Returns structured data.
     * @return array An associative array of structured data.
     */
    abstract function get_data();
}

// TODO: Debug before exposing
class _TlFuzzyProvider extends TlProvider {

    function init() {
        $this->lines = new TlLinesProvider($this->params);
        $this->stations = new TlStationsProvider($this->params);
        $this->departures = new TlDeparturesProvider($this->params);
    }

    function get_data() {
        $line = @$this->params['line'];
        $station = @$this->params['station'];
        $direction = @$this->params['direction'];
        return $this->goget('departures', 'stations', 'lines');
    }

    function goget() {
        $types_order = func_get_args();
        foreach ($types_order as $position => $type) {
            $data = $this->$type->get();
            if (!$data[$type]) continue;
            $a = array();
            if (false) {
                $suggest = new TlSuggestProvider($this->params);
                return array_merge(
                    array('type'=>$type),
                    $suggest->get()
                );
            }
            return array(
                'type' => $type,
                'data' => $data
            );
        }
    }
}

// TODO: Debug before exposing
class _TlSuggestProvider extends TlProvider {

    function init() {
        $this->lines = new TlLinesProvider($this->params);
        $this->stations = new TlStationsProvider($this->params);
        //$this->departures = new TlDeparturesProvider($this->params);
    }

    function get_data() {
        $type = @$this->params['type'];
        $word = @$this->params['word'];
        if (!$type) throw new Exception('Missing type parameter');
        $data = $this->$type->get();
        $data = array_keys($data[$type]);
        return array(
            'suggest' => $this->suggest($word, $data),
            'data' => $data
        );
    }

    function suggest($input, $words) {
        $shortest = -1;
        foreach ($words as $word) {
            $lev = levenshtein($input, $word);
            if ($lev == 0) {
                $closest = $word;
                $shortest = 0;
                break;
            }
            if ($lev <= $shortest || $shortest < 0) {
                $closest  = $word;
                $shortest = $lev;
            }
        }
        $percent = 1 - levenshtein($input, $closest) / max(strlen($input), strlen($closest));
        return array(
            'closest' => $closest,
            'percent' => $percent
        );
    }
}

class TlDeparturesProvider extends TlProvider {

    function init() {
        if (!@$this->params['station']) throw new Exception(implode(' ', array(
            "Missing 'station' parameter.",
            "Use the 'stations' provider for available stations",
            "(use station 'name' property)."
        )));
        $stations = new TlStationsProvider($this->params);
        $stations = $stations->get();
        $this->stations = $stations;
    }

    function get_data() {
        $url = 'http://www.t-l.ch/htr.php';
        $ligne = $this->params['line'];
        $arrets = $this->get_station_by_name($this->params['station']);
        $sens = @$this->params['direction'];
        $sens = $sens ? array($sens) : array('A', 'R');
        $data = array();
        foreach ($sens as $s) {
            $u = $url.'?'.http_build_query(array(
                'ligne' => $ligne,
                'arret' => $arrets[$s],
                'sens' => $s
            ));
            $station_data = $this->get_station_data($this->fetch_url($u));
            $departures = @$station_data['departures'] ? $station_data['departures'] : array();
            foreach ($departures as $departure) {
                $d = array();
                $d['time'] = $departure;
                $d['direction'] = $s;
                $d['destination'] = $station_data['destination'];
                $data[] = $d;
            }
            //$data['line'] = $station_data['line'];
            //$data['line_name'] = $station_data['line_name'];
            //$data['line_source'] = $station_data['line_source'];
            //$data['line_destination'] = $station_data['line_destination'];
        }
        return $data;
    }

    function get_station_by_name($name) {
        foreach ($this->stations as $station)
            if (strtolower($station['name']) == trim(strtolower($name)))
                return $station;
        throw new Exception('Station not found');
    }

    function get_station_data($data) {
        preg_match('/<\w* id="e_nom_arret"\>(.*?)<\/\w*>/i', $data, $station);
        preg_match('/<\w* id="e_ligne"\>(.*?)<\/\w*>/i', $data, $line);
        preg_match('/<\w* id="nom".*?\>(.*?)<\/\w*>/i', $data, $line_name);
        preg_match_all('/<\w* id="e_minutes"\>(\d*?)<\/\w*> min/i', $data, $realtime_matches);
        preg_match_all('/<\w* id="e_heures"\>(.*?)<\/\w*>/i', $data, $timetable_matches);
        preg_match('/Aucun passage.*/', $data, $error);
        $error = @$error[0];
        list($line_source, $line_destination) = explode(' - ', $line_name[1]);
        $departures = array_merge(
            array_map(array($this, '_add_unit'), $realtime_matches[1]),
            $timetable_matches[1]
        );
        if ($error) return array();
        return array(
            'departures' => $departures,
            'station' => $station[1],
            'line' => $line[1],
            'line_name' => $line_name[1],
            'source' => $line_source,
            'destination' => $line_destination
        );
    }
    protected function _add_unit($string) { return "{$string} min"; }
}

class TlStationsProvider extends TlProvider {

    function make_url() {
        $line = $this->params['line'];
        if (!$line) throw new Exception(implode(' ', array(
            "Missing 'line' parameter.",
            "Use the 'lines' provider for available lines",
            "(use station 'id' property)."
        )));
        return "http://www.t-l.ch/index.php?option=com_tl&task=get_arrets&Itemid=3&format=raw&choix=11&line={$line}";
    }

    function get_data() {
        $data = $this->fetch_url($this->make_url());
        // Line
        preg_match('/Ligne: (.*?)</i', $data, $matches);
        $line = @$matches[1];
        // Go & Return separation
        preg_match_all('/haller(.*?)hretour(.*)/i', $data, $matches);
        $directions = array(
            'A' => $matches[1][0], // Aller
            'R' => $matches[2][0]  // Retour
        );
        // Actual parsing
        $stations = array();
        foreach($directions as $direction => $data) {
            preg_match_all('/<a.*?href=".*?\/.*?_.*?_(.*?).pdf ">(.*?)<\/a>/i', $data, $matches);
            for ($i=0; $i<count($matches[0]); $i++) {
                $root = substr($matches[1][$i], 0, -2);
                $stations[$root]['name'] = $matches[2][$i];
                $stations[$root]['root'] = $root;
                $stations[$root]["{$direction}"] = $matches[1][$i];
            }
        }
        // Changes unique root keys to numeric keys
        $result = array();
        foreach($stations as $station) $result[] = $station;
        // Data
        return $result;
    }
}

class TlLinesProvider extends TlProvider {

    function make_url() {
        return "http://www.t-l.ch/horaires-par-arrets.html";
    }

    function get_data() {
        $data = $this->fetch_url($this->make_url());
        preg_match_all('/<.+?horaires_numbers_box.+?name="(.*?)".*?>(.*?)<.+?>/i', $data, $matches);
        for ($i=0; $i<count($matches[0])-1; $i++)
            $lines[] = array(
                'id' => $matches[1][$i],
                'name' => $matches[2][$i]
            );
        return $lines;
    }
}

class TlEventsProvider extends TlProvider {

    function make_url() {
        return "http://www.http://t-l.ch/manifestations-et-chantiers.html";
    }

    function get_data() {
        return 'TODO';
        $data = $this->fetch_url($this->make_url());
        preg_match_all('/TODO/i', $data, $matches);
        return array(
            'events' => $events
        );
    }
}


// Data collector draft ////////////////////////////////////////////////////////

abstract class TlCollector {

    function __construct() {}

}

class TlLinesCollector extends TlCollector {
    function collect() {
        $lines = new TlLinesProdiver();
        return $lines->get();
    }
}

class TlStationsCollector extends TlCollector {

    function collect() {
        $result = array();
        $lines = new TlLinesProvider();
        $lines = $lines->get();
        foreach($lines as $line) {
            echo "Processing line: {$line['name']}... ";
            $stations = new TlStationsProvider();
            $stations->params['line'] = $line['id'];
            $stations = $stations->get();
            echo "Parsing stations: ".count($stations)."... ";
            foreach($stations as $station) {
                // Checks if station already exists
                $station_exists = false;
                foreach ($result as &$item) {
                    if($station['name'] == $item['name'] &&
                       $station['A'] == $item['A'] &&
                       $station['R'] == $item['R']) {
                        $station_exists = true;
                        $item['_lines'][] = $line;
                        echo "Duplicate: {$station['name']}... ";
                        break;
                    }
                }
                // Adds station if it does not exists
                $station['_lines'][] = $line;
                if (!$station_exists) $result[] = $station;
            }
            echo "Result size: ".count($result);
            sleep(1);
            echo "\n";
        }
        return $result;
    }

}
