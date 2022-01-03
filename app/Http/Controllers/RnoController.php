<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Video;
use Drnxloc\LaravelHtmlDom\HtmlDomParser;


class RnoController extends Controller
{
    public function index()
    {
        $html = $this->curl();

        $dom = HtmlDomParser::str_get_html($html);
        $items = collect([]);
        foreach ($dom->find('.vid_thumbainl') as $item) {
            $time = $item->find('.vid-time')[0]->innertext;
            $url = $item->find('a')[0]->href;
            $items->push([
                'time' => $time,
                'second' => $this->timeToSeconds($time),
                'url' => $url
            ]);
        }
        $this->arrayToDb($items);
        dd($items);
    }

    public function timeToSeconds(string $time): int
    {
        $arr = explode(':', $time);
        if (count($arr) == 3) {
            return $arr[0] * 3600 + $arr[1] * 60 + $arr[2];
        } else {
            return $arr[0] * 60 + $arr[1];
        }
    }

    public function curl()
    {
        $page = request()->page ?? 1;
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://earneo.tube/web-api/videos/recent?page=" . $page,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30000,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => [
                "accept: */*",
                "accept-language: en-US,en;q=0.8",
                "content-type: application/json",
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);
        return $response;
    }

    public function arrayToDb($array)
    {
        foreach ($array as $item) {
            $exp = explode('/', $item['url']);
            $end = end($exp);
            $data = Video::find(intval($end));
            if (!$data) {
                $data = new Video();
            }
            $data->id = intval($end);
            $data->url = $item['url'];
            $data->time = $item['time'];
            $data->second = $item['second'];
            $data->save();
        }
    }

    public function setErrorVideo()
    {
        $userId = request()->user_id ?? 1;
        $user = User::find($userId);
        $videoId = request()->video_id ?? 0;
        $video = Video::find($videoId);
        if ($video) {
            $user->histories()->where('video_id', $videoId)->delete();
            $video->status = 0;
            $video->save();
        }
    }

    public function history()
    {
        $userId = request()->user_id ?? 1;
        $user = User::find($userId);
        if (!$user->select_videos or !count($user->select_videos)) {
            $this->setSelectVideos($user);
        }
        $selectVideos = $user->select_videos;
        shuffle($selectVideos);
        $turn = true;
        $error = false;
        while ($turn) {
            $selectId = array_shift($selectVideos);
            $video = Video::find($selectId);
            if ($video->status === 1) {
                $turn = false;
            } else {
                if (!count($selectVideos)) {
                    $error = true;
                    $turn = false;
                    $this->setSelectVideos($user);
                }
            }
        }
        if ($error) {
            return response()->json([
                'status' => 2,
                'message' => 'Error'
            ], 200);
        }

        $user->histories()->create(['video_id' => $selectId]);
        return response()->json([
            'status' => 1,
            'id' => $selectId,
            'url' => "https://earneo.tube/video/" . $selectId
        ], 200);
    }

    public function setHistory()
    {
        $userId = request()->user_id ?? 1;
        $user = User::find($userId);
        $balance = request()->balance ?? 0;
        $currentVideo = request()->current ?? null;
        $lastHistory = $user->histories()->where('video_id', "<>", $currentVideo)->orderBy('created_at', 'desc')->first();
        if ($lastHistory) {
            $lastHistory->earning = floatval($balance) - floatval($user->balance);
            $lastHistory->save();
        }
        $user->balance = floatval($balance);
        $user->save();
        return response()->json([
            'id' => $userId,
            'balance' => $balance
        ]);
    }

    public function setSelectVideos($user)
    {
        $videos = Video::where('second', '>', 200)
            ->where('second', '<', 900)
            ->whereNotIn('id', $user->histories->pluck('video_id'))
            ->orderByRaw("RAND()")
            ->limit(100)->get()->pluck('id')->toArray();
        $user->select_videos = $videos;
        $user->save();
    }

    public function lastHistories()
    {
        $userId = request()->user_id ?? 1;
        $user = User::find($userId);
        $hour = request()->hour ?? 1;
        $histories1Hour = $user->histories()->lastxhour($hour)->sum('earning');
        dd($histories1Hour);
    }
}
