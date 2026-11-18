<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeletedSurveyPhotos
{
    public static function cleanup(): int
    {
        DB::table('pending_photo_deletions')->orderBy('id')->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                try {
                    if (Storage::disk('local')->delete($row->path)) {
                        DB::table('pending_photo_deletions')->where('id', $row->id)->delete();
                    }
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        });
        return DB::table('pending_photo_deletions')->count();
    }
}
