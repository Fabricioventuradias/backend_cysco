<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Video;
use App\Models\WhyChooseUsHolder;
use App\Traits\MerchantTrait;
use Illuminate\Validation\Rule;


class VideoUploadController extends Controller
{
    use MerchantTrait;

    public function index()
    {
        $merchant_id = get_merchant_id();

        $videos = Video::where('merchant_id', $merchant_id)
            ->orderBy('id', 'desc')
            ->get();

        $videos->transform(function ($video) use ($merchant_id) {
            $video->video_url = get_video($video->video, $merchant_id);
            return $video;
        });

        return view('merchant.video.index', compact('videos'));
    }

    public function create()
    {
        $merchant = get_merchant_id(false);
        $string_file = $this->getStringFile(NULL, $merchant);
        $arr_segment = add_blank_option(get_merchant_segment(true,$merchant->id,array(1, 4)),trans("$string_file.select"));
        return view('merchant.video.add',compact('arr_segment'));
    }

    public function store(Request $request)
    {
        $merchant_id = get_merchant_id();
    
        $mediaType = $request->input('media_type', 'video');
        $mimeRule = $mediaType === 'gif' ? 'mimes:gif' : 'mimes:mp4,mov,avi,mkv';
        if ($mediaType !== 'gif') {
            $request->merge(['segment_id' => null]);
        }
    
        $segmentRules = ['nullable'];
        if ($mediaType === 'gif') {
            $segmentRules[] = 'exists:segments,id';
            $segmentRules[] = Rule::unique('videos')->where(function ($query) use ($merchant_id, $mediaType) {
                return $query->where('merchant_id', $merchant_id)
                             ->where('media_type', $mediaType);
            });
        }
    
        $validated = $request->validate([
            'title'       => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'video'       => ['required', $mimeRule, 'max:20000'],
            'status'      => 'nullable|in:1,2',
            'type'        => 'nullable|in:1,2',
            'media_type'  => 'nullable|in:video,gif',
            'segment_id'  => $segmentRules,
            'holder_type' =>'nullable',
        ]);
    
        $fileName = upload_video('video', $merchant_id);
    
        $data = [
            'merchant_id' => $merchant_id,
            'title'       => $validated['title'] ?? null,
            'description' => $validated['description'] ?? null,
            'video'       => $fileName,
            'status'      => $validated['status'] ?? 1,
            'type'        => $validated['type'] ?? 1,
            'media_type'  => $mediaType,
            'segment_id'  => $mediaType === 'gif' ? ($validated['segment_id'] ?? null) : null,
            'holder_type'  => $validated['holder_type'] ?? 1,
        ];
    
        Video::create($data);
    
        return redirect()
            ->route('merchant.video.index')
            ->with('success', 'Video uploaded successfully.');
    }

    public function edit($id)
    {
        $merchant = get_merchant_id(false);
        $merchant_id = $merchant->id;
        $video = Video::where('merchant_id', $merchant_id)->findOrFail($id);
        $video->video_url = get_video($video->video, $merchant_id);
    
        $string_file = $this->getStringFile(NULL, $merchant);
        $arr_segment = add_blank_option(get_merchant_segment(true, $merchant->id, array(1, 4)), trans("$string_file.select"));
    
        return view('merchant.video.edit', compact('video', 'arr_segment'));
    }

    public function update(Request $request, $id)
    {
        $merchant_id = get_merchant_id();
        $video = Video::where('merchant_id', $merchant_id)->findOrFail($id);
    
        $mediaType = $request->input('media_type', $video->media_type);
        $mimeRule = $mediaType === 'gif' ? 'mimes:gif' : 'mimes:mp4,mov,avi,mkv';
    
        if ($mediaType !== 'gif') {
            $request->merge(['segment_id' => null]);
        }
    
        $segmentRules = ['nullable'];
        if ($mediaType === 'gif') {
            $segmentRules[] = 'exists:segments,id';
            $segmentRules[] = Rule::unique('videos')->where(function ($query) use ($merchant_id, $mediaType) {
                return $query->where('merchant_id', $merchant_id)
                             ->where('media_type', $mediaType);
            })->ignore($video->id);
        }
    
        $validated = $request->validate([
            'title'       => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'video'       => ['nullable', $mimeRule, 'max:20000'],
            'status'      => 'nullable|in:1,2',
            'type'        => 'nullable|in:1,2',
            'media_type'  => 'nullable|in:video,gif',
            'segment_id'  => $segmentRules,
            'holder_type' => 'nullable',
        ]);
    
        if ($request->hasFile('video')) {
            delete_video($video->video, $merchant_id);
            $validated['video'] = upload_video('video', $merchant_id);
        } else {
            unset($validated['video']);
        }
    
        $validated['status']     = $validated['status'] ?? $video->status;
        $validated['type']       = $validated['type'] ?? $video->type;
        $validated['holder_type']= $validated['holder_type'] ?? $video->holder_type;
        $validated['media_type'] = $mediaType;
        $validated['segment_id'] = $mediaType === 'gif'
            ? ($validated['segment_id'] ?? $video->segment_id ?? null)
            : null;
    
        $video->update($validated);
    
        return redirect()
            ->route('merchant.video.index')
            ->with('success', 'Video updated successfully.');
    }

    public function destroy($id)
    {
        $merchant_id = get_merchant_id();
        $video = Video::where('merchant_id', $merchant_id)->findOrFail($id);

        delete_video($video->video, $merchant_id);
        $video->delete();

        return redirect()
            ->route('merchant.video.index')
            ->with('success', 'Video deleted successfully.');
    }
    
     public function whyindex()
    {
        $merchantId = get_merchant_id();
        // dd($merchantId);

        $this->ensureHolders($merchantId);

        $holders = WhyChooseUsHolder::where('merchant_id', $merchantId)
            ->orderBy('id')
            ->get();

        return view('merchant.video.whyindex', compact('holders'));
    }
    public function whyedit($id)
    {
        $holder = WhyChooseUsHolder::findOrFail($id);

        return view('merchant.video.whyedit', compact('holder'));
    }
    public function whyupdate(Request $request, $id)
    {
        $merchantId = get_merchant_id();
        $holder = WhyChooseUsHolder::findOrFail($id);

        $request->validate([
            'title'       => 'required|string|max:255',
            'icon'        => 'nullable|image|mimes:jpg,jpeg,png,svg,webp|max:2048',
            'description' => 'required|string|max:500',
        ]);

        $data = [
            'title'       => $request->title,
            'description' => $request->description,
        ];

        if ($request->hasFile('icon')) {
             $data['icon'] = upload_video('icon', $merchantId);
        }
        // dd( $data['icon']);


        $holder->update($data);

        return redirect()
            ->route('merchant.whychooseus.index')
            ->with('success', 'Why Choose Us section updated successfully.');
    }
    private function ensureHolders($merchantId)
    {
        $existing = WhyChooseUsHolder::where('merchant_id', $merchantId)->count();

        if ($existing >= 4) {
            return;
        }

        for ($i = $existing; $i < 4; $i++) {
            WhyChooseUsHolder::create([
                'merchant_id' => $merchantId,
                'title'       => null,
                'icon'        => null,
                'description' => null,
            ]);
        }
    }
}