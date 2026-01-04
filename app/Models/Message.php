<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'content',
        'file_size',
        'file_url',
        'file_name',
        'type',
    ];

    protected $appends = [
        'file_path',
    ];

    public function getFilePathAttribute()
    {
        if (!$this->file_url) return null;

        return config('filesystems.disks.s3.url') . '/' .
            (config('filesystems.disks.s3.folder_path') ? config('filesystems.disks.s3.folder_path') . '/' : '') .
            ltrim($this->file_url, '/');
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
