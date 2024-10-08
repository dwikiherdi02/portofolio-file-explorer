<?php

namespace App\Http\Controllers;

use App\Http\Requests\DirectoryRequest;
use Facades\App\Repositories\FileRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Facades\App\Repositories\DirectoryRepository;

class DirectoryController extends Controller
{
    public function listRoot(): JsonResponse
    {
        $data = DirectoryRepository::findAll(
            // selects: ['id', 'name', 'image', DB::raw('breadcrumbs_json AS breadcrumbs')],
            selects: ['id', 'name', 'image'],
            wheres: ['is_root' => 1],
            limit: 0,
        )->toArray();

        if (count($data) > 0) {
            return response()->json(
                [
                    'code' => Response::HTTP_OK,
                    'message' => Response::$statusTexts[Response::HTTP_OK],
                    'results' => $data,
                    'errors' => [],
                ],
                Response::HTTP_OK
            );
        } else {
            return response()->json(
                [
                    'code' => Response::HTTP_NOT_FOUND,
                    'message' => Response::$statusTexts[Response::HTTP_NOT_FOUND],
                    'results' => [],
                    'errors' => [],
                ],
                Response::HTTP_NOT_FOUND
            );
        }

    }

    public function list(DirectoryRequest $request): JsonResponse
    {
        if ($errors = $request->validating('list')) {
            return response()->json(
                [
                    'code' => Response::HTTP_BAD_REQUEST,
                    'message' => Response::$statusTexts[Response::HTTP_BAD_REQUEST],
                    'results' => [],
                    'errors' => $errors,
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        if (empty($request->input('subid'))) {
            $currentDir = DirectoryRepository::findByID(id: $request->input('rid'), selects: ['id', 'root_id', 'parent_id', 'name', 'image', DB::raw('breadcrumbs_json as breadcrumbs')]);
        } else {
            $currentDir = DirectoryRepository::find(selects: ['id', 'root_id', 'parent_id', 'name', 'image', DB::raw('breadcrumbs_json as breadcrumbs')], wheres: ['id' => $request->input('subid'), 'root_id' => $request->input('rid')]);
        }


        $where = ['parent_id' => $request->input('rid')];
        if ($request->input('subid') && ($request->input('subid') != $request->input('rid'))) {
            $where = ['parent_id' => $request->input('subid'), 'root_id' => $request->input('rid')];
        }

        $list = DirectoryRepository::findAll(
            selects: ['id', 'root_id', 'name', 'image', DB::raw("'dir' as type")],
            wheres: $where,
            limit: 0,
        )->toArray();

        $files = FileRepository::findAll(
            selects: ['filename_ori', 'icon', 'path', 'filename', DB::raw("'file' as type")],
            wheres: ['fileable_type' => get_class($currentDir), 'fileable_id' => $currentDir->id],
            limit: 0,
        );

        foreach ($files as $file) {
            array_push($list, [
                'filename_ori' => $file->filename_ori,
                'icon' => $file->icon,
                'link' => $file->link,
                'type' => $file->type,
            ]);
        }

        if (!empty($currentDir)) {
            return response()->json(
                [
                    'code' => Response::HTTP_OK,
                    'message' => Response::$statusTexts[Response::HTTP_OK],
                    'results' => [
                        'current' => $currentDir,
                        'list' => $list,
                    ],
                    'errors' => [],
                ],
                Response::HTTP_OK
            );
        } else {
            return response()->json(
                [
                    'code' => Response::HTTP_NOT_FOUND,
                    'message' => Response::$statusTexts[Response::HTTP_NOT_FOUND],
                    'results' => [],
                    'errors' => [],
                ],
                Response::HTTP_NOT_FOUND
            );
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(DirectoryRequest $request): JsonResponse
    {
        if ($errors = $request->validating('store')) {
            return response()->json(
                [
                    'code' => Response::HTTP_BAD_REQUEST,
                    'message' => Response::$statusTexts[Response::HTTP_BAD_REQUEST],
                    'results' => [],
                    'errors' => $errors,
                ],
                Response::HTTP_BAD_REQUEST
            );
        }

        // $parentID = DirectoryRepository::findByID($request->get('parent_id'));
        $data = [
            // 'parent_id' => $parentID->id,
            // 'root_id' => ($parentID->root_dir) ? $parentID->root_dir : $parentID->name,
            'parent_id' => $request->get('parent_id'),
            'name' => $request->get('folder_name'),
            'is_root' => false,
        ];

        if (!DirectoryRepository::storing($data)) {
            return response()->json(
                [
                    'code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'message' => Response::$statusTexts[Response::HTTP_INTERNAL_SERVER_ERROR],
                    'results' => [],
                    'errors' => ['message' => Response::$statusTexts[Response::HTTP_INTERNAL_SERVER_ERROR]],
                ],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }


        return response()->json(
            [
                'code' => Response::HTTP_CREATED,
                'message' => Response::$statusTexts[Response::HTTP_CREATED],
                'results' => [],
                'errors' => [],
            ],
            Response::HTTP_CREATED
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
