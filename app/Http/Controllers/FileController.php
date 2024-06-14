<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\File;
use App\Http\Requests\StoreFolderRequest;
use App\Http\Requests\StoreFileRequest;
use App\Http\Requests\FilesActionRequest;
use App\Http\Resources\FileResource;

class FileController extends Controller
{
    public function myFiles(Request $request, string $folder = null) {
        if($folder) {
            $folder = File::query()
                ->where('created_by', Auth::id())
                ->where('path', $folder)
                ->firstOrFail();
        }
        if(!$folder){
            $folder = $this->getRoot();
        }

        $files = File::query()
            ->where('parent_id', $folder->id)
            ->where('created_by', Auth::id())
            ->orderBy('is_folder', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(14);

        $files = FileResource::collection($files); 

        if ($request->wantsJson()) {
            return $files;
        }
        
        $ancestors = FileResource::collection([...$folder->ancestors, $folder]);

        $folder = new FileResource($folder);

        return Inertia::render('MyFiles', compact('files', 'folder', 'ancestors'));
    }

    public function createFolder(StoreFolderRequest $request) {
        $data = $request->validated();
        $parent = $request->parent;

        if(!$parent) {
            $parent = $this->getRoot();
        }

        $file = new File();
        $file->is_folder = 1;
        $file->name = $data['name'];

        $parent->appendNode($file);
    }

    public function store(StoreFileRequest $request) {
        $data = $request->validated();
        $parent = $request->parent;
        $user = $request->user();
        $fileTree = $request->file_tree;
        
        if (!$parent) {
            $parent = $this->getRoot();
        }

        if (!empty($fileTree)) {
            $this->saveFileTree($fileTree, $parent, $user);
        } else {
            foreach ($data['files'] as $file) {
                /** @var \Illuminate\Http\UploadedFile $file */

                $this->saveFile($file, $user, $parent);
            }
        }
    }

    private function getRoot() {
        return File::query()->whereIsRoot()->where('created_by', Auth::id())->firstOrFail();
    }

    public function saveFileTree($fileTree, $parent, $user)
    {
        foreach ($fileTree as $name => $file) {
            if (is_array($file)) {
                $folder = new File();
                $folder->is_folder = 1;
                $folder->name = $name;

                $parent->appendNode($folder);
                $this->saveFileTree($file, $folder, $user);
            } else {

                $this->saveFile($file, $user, $parent);
            }
        }
    }

    private function saveFile($file, $user, $parent): void
    {
        $path = $file->store('/files/' . $user->id, 'local');

        $model = new File();
        $model->storage_path = $path;
        $model->is_folder = false;
        $model->name = $file->getClientOriginalName();
        $model->mime = $file->getMimeType();
        $model->size = $file->getSize();
        // $model->uploaded_on_cloud = 0

        $parent->appendNode($model);

        // UploadFileToCloudJob::dispatch($model);
    }

    public function destroy(FilesActionRequest $request) {

        $data = $request->validated();
        $parent = $request->parent;

        if ($data['all']) {
            $children = $parent->children;

            foreach ($children as $child) {
                $child->delete();
            }
        } else {
            foreach ($data['ids'] ?? [] as $id) {
                $file = File::find($id);
                if ($file) {
                    // if ($file->is_folder) {
                    //     $children = $file->children;

                    //     foreach ($children as $child) {
                    //         $child->delete();
                    //     }
                    // }
                    $file->delete();
                }
            }
        }

        return to_route('myFiles', ['folder' => $parent->path]);
    }

    public function download(FilesActionRequest $request) {
        $data = $request->validated();
        $parent = $request->parent;

        $all = $data['all'] ?? false;
        $ids = $data['ids'] ?? [];

        if (!$all && empty($ids)) {
            return [
                'message' => 'Please select files to download'
            ];
        }

        if ($all) {
            $url = $this->createZip($parent->children);
            $filename = $parent->name . '.zip';
        } else {
            if (count($ids) == 1) {
                $file = File::find($ids[0]);
                if ($file->is_folder) {
                    if ($file->children->count() == 0) {
                        return [
                            'message' => 'The folder is empty'
                        ];
                    }
                    $url = $this->createZip($file->children);
                    $filename = $file->name . '.zip';
                } else {
                    $dest = 'public/' . pathinfo($file->storage_path, PATHINFO_BASENAME);
                    Storage::copy($file->storage_path, $dest);

                    $url = asset(Storage::url($dest));
                    $filename = $file->name;
                }
            } else {
                $files = File::query()->whereIn('id', $ids)->get();
                $url = $this->createZip($files);

                $filename = $parent->name . '.zip';
            }
        }

        return [
            'url' => $url,
            'filename' => $filename
        ];
    }

    public function createZip($files): string
    {
        $zipPath = 'zip/' . Str::random() . '.zip';
        $publicPath = "$zipPath";

        if (!is_dir(dirname($publicPath))) {
            Storage::disk('public')->makeDirectory(dirname($publicPath));
        }

        $zipFile = Storage::disk('public')->path($publicPath);

        $zip = new \ZipArchive();

        if ($zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            $this->addFilesToZip($zip, $files);
        }

        $zip->close();

        return asset(Storage::disk('local')->url($zipPath));
    }

    private function addFilesToZip($zip, $files, $ancestors = '')
    {
        foreach ($files as $file) {
            if ($file->is_folder) {
                $this->addFilesToZip($zip, $file->children, $ancestors . $file->name . '/');
            } else {
                $localPath = Storage::disk('local')->path($file->storage_path);
                // if ($file->uploaded_on_cloud == 1) {
                //     $dest = pathinfo($file->storage_path, PATHINFO_BASENAME);
                //     $content = Storage::get($file->storage_path);
                //     Storage::disk('public')->put($dest, $content);
                //     $localPath = Storage::disk('public')->path($dest);
                // }

                $zip->addFile($localPath, $ancestors . $file->name);
            }
        }
    }
}


