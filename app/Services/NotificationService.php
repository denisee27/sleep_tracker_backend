<?php

namespace App\Services;

use App\Models\UserNotification;

class NotificationService
{
    /**
     * user
     *
     * @var mixed
     */
    protected $user;

    /**
     * __construct
     *
     * @param  mixed $user
     * @return void
     */
    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * create
     *
     * @return void
     */
    public function create($title, $desc, $url)
    {
        $item = new UserNotification();
        $item->user_id = $this->user->id;
        $item->title = $title;
        $item->description = $desc;
        $item->url = $url;
        $item->save();
    }

    /**
     * getAll
     *
     * @return UserNotification
     */
    public function getAll()
    {
        $items = UserNotification::query();
        $items->where('user_id', $this->user->id);
        return $items;
    }

    /**
     * getCount
     *
     * @param  mixed $all
     * @return void
     */
    public function getCount($all = false)
    {
        $items = UserNotification::query();
        $items->where('user_id', $this->user->id);
        if (!$all) {
            $items->where('is_read', 0);
        }
        return $items->count();
    }

    /**
     * read
     *
     * @param  mixed $notif_id
     * @return void
     */
    public function read($notif_id)
    {
        if ($notif_id) {
            UserNotification::where('id', $notif_id)->update(['is_read' => 1]);
        } else {
            UserNotification::where('user_id', $this->user->id)->update(['is_read' => 1]);
        }
    }

    /**
     * delete
     *
     * @param  mixed $ids
     * @return void
     */
    public function delete($ids = [])
    {
        if (count($ids)) {
            UserNotification::whereIn('id', $ids)->delete();
        } else {
            UserNotification::where('user_id', $this->user->id)->delete();
        }
    }
}
