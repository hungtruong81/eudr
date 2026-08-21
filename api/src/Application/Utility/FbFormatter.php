<?php

declare(strict_types=1);

namespace App\Application\Utility;

class FbFormatter
{
    public static function convertCampaignToFacebookPayload($campaign, $assetRepository, $s3)
    {

        if (empty($campaign['storeAppInfo'])) {
            $campaign['storeAppInfo'] = [
                "platform" => "",
                "applicationId" => "",
                "storeUrl" => "",
            ];
        }


        $adsets = array_map(function ($adset) use ($campaign) {
            $return_adset = self::convertAdsetToFacebookPayload($adset, $campaign);

            // action
            $return_adset['zps_action'] = $adset['zps_action'];

            return $return_adset;
        }, $campaign['adsets']);


        $creatives = array_reduce($campaign['adsets'], function ($acc, $adset) {
            $ads = array_map(function ($ad) use ($adset) {
                return [...$ad['creative'], 'adset' => $adset];
            }, $adset['ads']);
            return array_merge($acc, $ads);
        }, []);


        $newCreatives = array_map(function ($creative) use ($campaign, $assetRepository, $s3) {
            $return_creative = [];
            if (!empty($creative["asset_code"])) {
                $asset = $assetRepository->findAssetOfCode($creative["asset_code"]);
                $return_creative = self::convertCreativeToFacebookCreatePayload($creative, $campaign, $asset, $s3);
            } else {
                $return_creative = self::convertCreativeToFacebookPayload($creative, $campaign);
            }

            $degrees_of_freedom_spec = [];
            if (empty($return_creative["asset_feed_spec"]['images']) && empty($return_creative["asset_feed_spec"]['videos'])) {
                $degrees_of_freedom_spec = self::convertDegreesOfFreedomSpecToFacebookPayload(
                    $return_creative['degrees_of_freedom_spec']??[],
                    $return_creative['asset_feed_spec']??[],
                    $campaign,
                    $return_creative['object_story_spec']??[]
                );
            }
            $return_creative['degrees_of_freedom_spec'] = $degrees_of_freedom_spec;

            // action
            $return_creative['zps_action'] = $creative['zps_action'];
            return $return_creative;
        }, $creatives);

        // echo json_encode($newCreatives);
        // die();


        $payload = [
            'id' => $campaign['id']??"",
            'name' => $campaign['name'],
            'objective' => $campaign['objective']??"OUTCOME_APP_PROMOTION",
            'special_ad_categories' => $campaign['special_ad_categories']??[],
            'is_skadnetwork_attribution' => $campaign['storeAppInfo']['platform'] === 'iOS',
            'adsets' => $adsets,
            'creatives' => $newCreatives,
            // Advantage campaign budget
            // 'daily_budget' => $campaign['daily_budget']??0,
            // 'bid_strategy' => $campaign['bid_strategy']??"LOWEST_COST_WITHOUT_CAP",
            // 'adset_budgets' => $campaign['adset_budgets']??[],
            // ...($campaign['zps_action']=="create_campaign"?['status' => 'PAUSED']:[]),
            'status' => $campaign['status'],
            'zps_action' => $campaign['zps_action'],
        ];
        if (!empty($campaign["bid_strategy"])) {
            $payload["bid_strategy"] = $campaign["bid_strategy"];
        }
        if (!empty($campaign['daily_budget'])) {
            $payload["daily_budget"] = $campaign["daily_budget"];
        }

        if ($campaign['storeAppInfo']['platform'] === 'iOS') {
            $payload['promoted_object'] = [
                'application_id' => $campaign['storeAppInfo']['applicationId'],
                'object_store_url' => $campaign['storeAppInfo']['storeUrl']
            ];
        }
        // self::removeId($payload);
        return $payload;
    }

    public static function convertAdsetToFacebookPayload($adset, $campaign)
    {
        // $ads = array_map([self::class, 'convertAdToFacebookPayload'], $adset['ads']);

        $ads = array_map(function ($ad) {
            $return_ad = self::convertAdToFacebookPayload($ad);
            // action
            $return_ad['zps_action'] = $ad['zps_action'];
            return $return_ad;
        }, $adset['ads']);


        $promoted_object = !empty($campaign['storeAppInfo']['applicationId'])
            ? [
                'application_id' => $campaign['storeAppInfo']['applicationId'],
                'object_store_url' => $campaign['storeAppInfo']['storeUrl']
            ]
            : ($adset['promoted_object'] ?? null);

        $payload = [
            'id' => $adset['id']??"",
            'name' => $adset['name'],
            'start_time' => floor(time()),
            // 'daily_budget' => $adset['daily_budget'],
            // 'bid_strategy' => $adset['bid_strategy']??"LOWEST_COST_WITHOUT_CAP",
            'billing_event' => $adset['billing_event']??"IMPRESSIONS",
            'optimization_goal' => $adset['optimization_goal']??"APP_INSTALLS",
            'campaign_attribution' => (!empty($campaign['storeAppInfo']) && $campaign['storeAppInfo']['platform'] === 'iOS') ? "AEM" : "",
            'targeting' => self::convertTargetingToFacebookPayload($adset['targeting']??[], $campaign),
            'promoted_object' => $promoted_object,
            'attribution_spec' => $adset['attribution_spec']??[
                [
                    "event_type"=> "CLICK_THROUGH",
                    "window_days"=> 1
                    ]
            ],
            // 'targeting_optimization' => self::convertTargetingOptimizationToFacebookPayload($adset['targeting_optimization'], $campaign),
            // 'targeting_optimization' => 'none',
            'status' => 'ACTIVE',
            'ads' => $ads,
            // Adset budget level
            // ...(!empty($adset["daily_budget"]) ?["daily_budget" => $adset["daily_budget"]] : []),
            ...(!empty($adset["bid_strategy"]) ?["bid_strategy" => $adset["bid_strategy"]] : []),
        ];
        if (!empty($adset['daily_budget'])) {
            $payload['daily_budget'] = $adset['daily_budget'];
            if (!empty($adset['bid_strategy'])) {
                $payload['bid_strategy'] = $adset['bid_strategy'];
            } else {
                $payload['bid_strategy'] = "LOWEST_COST_WITHOUT_CAP";
            }
        }

        return $payload;
    }
    public static function convertTargetingOptimizationToFacebookPayload($targeting_optimization, $campaign)
    {
        $newTargetingOptimization = [];

        $item = [
            'key' => 'detailed_targeting',
            'value' => 0
        ];
        $newTargetingOptimization[] = $item;

        /* foreach ($targeting_optimization as $type) {
            // Turn off Advantage detailed targeting
            if ($type['key'] === 'detailed_targeting') {
                $type['value'] = 0;
            }
            $item = [
                'key' => $type['key'],
                'value' => $type['value']
            ];
            $newTargetingOptimization[] = $item;
        } */

        return $newTargetingOptimization;
    }

    public static function convertTargetingToFacebookPayload($targeting, $campaign)
    {
        $countries = [];
        if (!empty($campaign["country"])) {
            $countries = [strtoupper($campaign['country']['code'])];
        } else if (!empty($targeting["geo_locations"]["countries"])){
            $countries = $targeting["geo_locations"]["countries"];
        }
        $user_os = [];
        if (!empty($campaign['storeAppInfo']['platform'])) {
            $user_os = $campaign['storeAppInfo']['platform'] === 'iOS'
                ? ['iOS_ver_14.0_and_above']
                : ['Android'];
        }

        $geo_locations = $targeting['geo_locations'] ?? [];
        if (!empty($countries)) {
            $geo_locations['countries'] = $countries;
        }

        $newTargeting = array_merge($targeting, [
            ...!empty($user_os) ? ['user_os' => $user_os] : [],
            // 'user_os' => $user_os,
            'geo_locations' => $geo_locations,
            /* 'targeting_automation' => [
                'advantage_audience' => 0
            ] */
            // ...!empty($targeting["targeting_automation"]) ? ['targeting_automation' => $targeting["targeting_automation"]] : [],
            'targeting_automation' => []
        ]);

        unset($newTargeting['targeting_optimization']); // You don\'t need to set a value for the targeting_optimization field because it has been removed. Advantage detailed targeting will be applied to your ad set.
        unset($newTargeting['user_device']); // Trường hợp có chọn OS thì không cần chọn device
        unset($newTargeting["geo_locations"]["location_types"]);
        // unset($newTargeting['age_max']);
        // unset($newTargeting['age_min']);
        // unset($newTargeting['age_range']);

        // fix age_max with TH
        if (isset($newTargeting['age_min']) && !empty($campaign['country'])) {
            $countryCode = $campaign['country']['code'];
            if ($countryCode === 'TH') {
                $newTargeting['age_min'] = 20;
            } elseif ($countryCode === 'ID') {
                $newTargeting['age_min'] = 21;
            }
        }

        // if (isset($newTargeting['age_range']) && !empty($campaign['country']) && in_array($campaign['country']['code'], ['TH', 'ID'])) {
            // vì gặp lỗi: The specified targeting spec is not valid because: The field targeting_automation must be enabled to use the field age_range.
            unset($newTargeting['age_range']);
        // }

        return $newTargeting;
    }

    public static function convertAdToFacebookPayload($ad)
    {
        $id = '';
        if (!empty($ad['creative']['id'])) {
            $id = $ad['creative']['id'];
        } elseif (!empty($ad['creative']['asset_code'])) {
            $id = $ad['creative']['asset_code'];
        }

        return [
            'name' => $ad['name'],
            'id' => $ad['id']??"",
            'creative' => [
                'need_replace_creative_id' => $id
            ],
            'status' => 'ACTIVE'
        ];
    }

    public static function convertCreativeToFacebookCreatePayload($creative, $campaign, $asset, $s3)
    {
        // Check 1 or multiple files
        $placements = $asset->getPlacements();
        $number_files = 0;
        $tmp_file_code = '';
        foreach ($placements as $k => $placement) {
            if ($tmp_file_code != $placement['file_code']) {
                $number_files++;
                $tmp_file_code = $placement['file_code'];
            }

            // update full_path
            if (!empty($placement['file_path'])) {
                $placements[$k]["file_path_full"] = Utils::signed_url($s3, AWS_S3_bucket, $placement['file_path'], 3600 * 72);
            }
            // update full_path
            if (!empty($placement['video_thumbnail_path'])) {
                $placements[$k]["video_thumbnail_path_full"] = Utils::signed_url($s3, AWS_S3_bucket, $placement['video_thumbnail_path'], 3600 * 72);
            }
        }
        $first_placement = $placements[0];

        $adset = $creative['adset'];

        $link_data = [];
        $video_data = [];

        $promoted_object = $campaign['storeAppInfo']
            ? [
                'application_id' => $campaign['storeAppInfo']['applicationId'],
                'object_store_url' => $campaign['storeAppInfo']['storeUrl']
            ]
            : [];

        if ($asset->getAssetType() == "image") {
            if ($number_files == 1) {
                $link_data = [
                    // "name" => $asset->getAssetName(),
                    "call_to_action" => ['type' => $asset->getCallToAction(),'value' => [
                            "link" => $promoted_object['object_store_url'] ?? '',
                            "app_link" => $adset['deep_link'] ?? '',
                        ],
                    ],
                    // "picture" => $first_placement['file_path_full'],
                    // "url_need_upload" => $first_placement['file_path_full'],
                    "hash" => $first_placement['fb_image_hash'],
                ];
            }
        } elseif ($asset->getAssetType() == "video") {
            if ($number_files == 1) {

                $video_data = [
                    "call_to_action" => ['type' => $asset->getCallToAction(),'value' => [
                            "link" => $promoted_object['object_store_url'] ?? '',
                            "app_link" => $adset['deep_link'] ?? '',
                        ],
                    ],
                    // "video_id" => 0,
                    // "url_need_upload" => $first_placement['file_path_full'],
                    "video_id" => $first_placement['fb_video_id'],
                    "image_url" => $first_placement['video_thumbnail_path_full'],
                ];
            }
        }

        if (!empty($campaign['pageInfo']['pageId'])) {
            $creative['object_story_spec'] = [
                'page_id' => $campaign['pageInfo']['pageId'],
                'instagram_user_id' => $campaign['pageInfo']['instagramUserId'],
            ];
        } else if (!empty($creative['object_story_spec'])) {
            $creative['object_story_spec'] = [
                'page_id' => $creative['object_story_spec']['page_id'],
                'instagram_user_id' => $creative['object_story_spec']['instagram_user_id'],
            ];
        } else {
            $creative['object_story_spec'] = [];
        }

        if (empty($campaign['storeAppInfo']['storeUrl'])) {
            $website_url = '';
            if (!empty($creative['asset_feed_spec']["link_urls"])) {
                if (!empty($creative['asset_feed_spec']["link_urls"][0]["website_url"])) {
                    $website_url = $creative['asset_feed_spec']["link_urls"][0]["website_url"];
                }
            }
        } else {
            $website_url = $campaign['storeAppInfo']['storeUrl'];
        }

        if ($number_files > 1) {
            $creative['asset_feed_spec'] = [
                // "optimization_type" => "DEGREES_OF_FREEDOM",
                // "optimization_type" => "REGULAR",
                "optimization_type" => "PLACEMENT",
                "ad_formats" => ["AUTOMATIC_FORMAT"],
            ];
        } else {
            $creative['asset_feed_spec'] = [];
        }
        $bodies = [];
        $titles = [];
        if ($number_files == 1) {

            if (!empty($link_data)) {
                $creative['object_story_spec']['link_data'] = $link_data;
            }
            if (!empty($video_data)) {
                $creative['object_story_spec']['video_data'] = $video_data;
            }

            // bodies
            foreach ($asset->getCreativeBodies() as $body) {
                if (empty($body)) continue;
                $bodies[] = [
                    "text" => $body
                ];
            }
            // titles
            foreach ($asset->getCreativeTitles() as $title) {
                if (empty($title)) continue;
                $titles[] = [
                    "text" => $title
                ];
            }
            if (!empty($bodies)) {
                $creative["asset_feed_spec"]["bodies"] = $bodies;
            }
            if (!empty($titles)) {
                $creative["asset_feed_spec"]["titles"] = $titles;
            }
            if (!empty($titles) || !empty($bodies)) {
                $creative["asset_feed_spec"]["optimization_type"] = "DEGREES_OF_FREEDOM";
            }
        } else {
            $call_to_action_types = [$asset->getCallToAction()];
            $images = [];
            $videos = [];

            $link_urls = [];
            $asset_customization_rules = [];

            // url_need_upload will change later
            foreach ($placements as $i => $placement) {
                // images or videos
                $it = [
                    "adlabels" => [["name" => 'placement_file_' . ($i + 1)]]
                ];
                if ($asset->getAssetType() == "image") {
                    $it["hash"] = $placement['fb_image_hash'];
                    $images[] = $it;
                } elseif ($asset->getAssetType() == "video") {
                    $it["video_id"] = $placement['fb_video_id'];
                    $it["thumbnail_url"] = $placement['video_thumbnail_path_full'];
                    $videos[] = $it;
                }
            }
            // bodies
            if (!empty($asset->getCreativeBodies())) {
                foreach ($asset->getCreativeBodies() as $body) {
                    $bodies[] = [
                        "text" => $body,
                        "adlabels" => [
                            ["name" => 'placement_body_1'],
                            ["name" => 'placement_body_2'],
                            ["name" => 'placement_body_3'],
                        ]
                    ];
                }
            } else {
                $bodies[] = [
                    "text" => "",
                    "adlabels" => [
                        ["name" => 'placement_body_1'],
                        ["name" => 'placement_body_2'],
                        ["name" => 'placement_body_3'],
                    ]
                ];
            }
            // titles
            if (!empty($asset->getCreativeTitles())) {
                foreach ($asset->getCreativeTitles() as $title) {
                    $titles[] = [
                        "text" => $title,
                        "adlabels" => [
                            ["name" => 'placement_title_1'],
                            ["name" => 'placement_title_2'],
                            ["name" => 'placement_title_3'],
                        ]
                    ];
                }
            } else {
                $titles[] = [
                    "text" => "",
                    "adlabels" => [
                        ["name" => 'placement_title_1'],
                        ["name" => 'placement_title_2'],
                        ["name" => 'placement_title_3'],
                    ]
                ];
            }



            $link_urls[] = [
                "website_url" => $website_url,
                "deeplink_url" => $adset['deep_link'] ?? '',
                "adlabels" => [
                    ["name" => 'placement_link_url_1'],
                    ["name" => 'placement_link_url_2'],
                    ["name" => 'placement_link_url_3'],
                ]
            ];

            if ($asset->getAssetType() == "image") {
                $label_name = "image_label";
            } else {
                $label_name = "video_label"; // changed to differentiate between labels
            }

            $asset_customization_rules = [
                [
                    "customization_spec" => [],
                    $label_name => [
                        "name" => "placement_file_1"
                    ],
                    "body_label" => [
                        "name" => "placement_body_1"
                    ],
                    "title_label" => [
                        "name" => "placement_title_1"
                    ],
                    "link_url_label" => [
                        "name" => "placement_link_url_1"
                    ],
                ],
                [
                    "customization_spec" => [
                        "publisher_platforms" => [
                            "facebook",
                            "instagram",
                            "audience_network",
                            "messenger"
                        ],
                        "facebook_positions" => [
                            "facebook_reels",
                            "story"
                        ],
                        "instagram_positions" => [
                            "profile_reels",
                            "story",
                            "reels"
                        ],
                        "messenger_positions" => [
                            "story"
                        ],
                        "audience_network_positions" => [
                            "classic",
                            "rewarded_video"
                        ]
                    ],
                    $label_name => [
                        "name" => "placement_file_2"
                    ],
                    "body_label" => [
                        "name" => "placement_body_2"
                    ],
                    "title_label" => [
                        "name" => "placement_title_2"
                    ],
                    "link_url_label" => [
                        "name" => "placement_link_url_2"
                    ],
                ],
                [
                    "customization_spec" => [
                        "publisher_platforms" => [
                            "facebook"
                        ],
                        "facebook_positions" => [
                            "search"
                        ]
                    ],
                    $label_name => [
                        "name" => "placement_file_3"
                    ],
                    "body_label" => [
                        "name" => "placement_body_3"
                    ],
                    "title_label" => [
                        "name" => "placement_title_3"
                    ],
                    "link_url_label" => [
                        "name" => "placement_link_url_3"
                    ],
                ]
            ];
            // Detect duplicate asset $images
            if (!empty($images[2])) {
                if ($images[2]["hash"]===$images[0]["hash"]) {
                    unset($images[2]);
                    unset($asset_customization_rules[2]);
                }
            } else if (!empty($videos[2])) {
                if ($videos[2]["video_id"]===$videos[0]["video_id"]) {
                    unset($videos[2]);
                    unset($asset_customization_rules[2]);
                }
            }

            $creative['asset_feed_spec']["call_to_action_types"] =  $call_to_action_types;
            $creative['asset_feed_spec']["link_urls"] = $link_urls;
            $creative['asset_feed_spec']["images"] = $images;
            $creative['asset_feed_spec']["videos"] = $videos;
            $creative['asset_feed_spec']["bodies"] = $bodies;
            $creative['asset_feed_spec']["titles"] = $titles;
            $creative['asset_feed_spec']["asset_customization_rules"] = $asset_customization_rules;
        }

        $object_story_spec = self::convertObjectStorySpecToFacebookPayload(
            $creative['object_story_spec']??[],
            $campaign
        );

        $asset_feed_spec = self::convertAssetFeedSpecToFacebookPayload(
            $creative['asset_feed_spec']??[],
            $campaign,
            $object_story_spec
        );

        $degrees_of_freedom_spec = [];

        // if (isset($asset_feed_spec['optimization_type']) && $asset_feed_spec['optimization_type']=='DEGREES_OF_FREEDOM') {
        /* if (empty($asset_feed_spec['images']) && empty($asset_feed_spec['videos'])) {
            $degrees_of_freedom_spec = self::convertDegreesOfFreedomSpecToFacebookPayload(
                [],
                $asset_feed_spec,
                $campaign,
                $object_story_spec
            );
        } */

        return [
            'need_replace_id' => $asset->getCode(),
            'name' => "Creative for " . $asset->getId(),
            'object_story_spec' => $object_story_spec,
            'asset_feed_spec' => $asset_feed_spec,
            ...!empty($degrees_of_freedom_spec) ? ['degrees_of_freedom_spec' => $degrees_of_freedom_spec] : [],
            // ...!empty($creative["titles"]) ? ['titles' => $creative["titles"]] : [],
            // ...!empty($creative["bodies"]) ? ['bodies' => $creative["bodies"]] : [],
        ];
    }
    public static function convertCreativeToFacebookPayload($creative, $campaign)
    {

        $object_story_spec = self::convertObjectStorySpecToFacebookPayload(
            $creative['object_story_spec']??[],
            $campaign
        );

        $asset_feed_spec = self::convertAssetFeedSpecToFacebookPayload(
            $creative['asset_feed_spec']??[],
            $campaign,
            $creative['object_story_spec']??[]
        );

        $degrees_of_freedom_spec = [];
        /* if (1==1 || empty($asset_feed_spec['images']) && empty($asset_feed_spec['videos'])) {
            $degrees_of_freedom_spec = self::convertDegreesOfFreedomSpecToFacebookPayload(
                [],
                $creative['asset_feed_spec']??[],
                $campaign,
                $creative['object_story_spec']??[]
            );
        } */
        /* if (!empty($asset_feed_spec['optimization_type']) && $asset_feed_spec['optimization_type']=='DEGREES_OF_FREEDOM') {
            $degrees_of_freedom_spec = self::convertDegreesOfFreedomSpecToFacebookPayload(
                $creative['degrees_of_freedom_spec']??[],
                $creative['asset_feed_spec']??[],
                $campaign,
                $creative['object_story_spec']??[]
            );
        } else {
            $degrees_of_freedom_spec = [];
        } */

        return [
            'need_replace_id' => $creative['id'],
            'name' => $creative['name'],
            'object_story_spec' => $object_story_spec,
            'asset_feed_spec' => $asset_feed_spec,
            'degrees_of_freedom_spec' => $degrees_of_freedom_spec,
        ];
        /* return [
            'object_story_spec' => self::convertObjectStorySpecToFacebookPayload(
                $creative['object_story_spec']??[],
                $campaign
            ),
            'asset_feed_spec' => self::convertAssetFeedSpecToFacebookPayload(
                $creative['asset_feed_spec']??[],
                $campaign,
                $creative['object_story_spec']??[]
            ),
            'degrees_of_freedom_spec' => self::convertDegreesOfFreedomSpecToFacebookPayload(
                $creative['degrees_of_freedom_spec']??[],
                $creative['asset_feed_spec']??[],
                $campaign,
                $creative['object_story_spec']??[]
            ),
        ]; */
    }

    public static function convertDegreesOfFreedomSpecToFacebookPayload($degrees_of_freedom_spec, $asset_feed_spec, $campaign, $object_story_spec)
    {
        // https://developers.facebook.com/docs/marketing-api/creative/advantage-creative/get-started
        /* $degrees_of_freedom_spec = [
            "creative_features_spec" => [
                ...!empty($degrees_of_freedom_spec['creative_features_spec']['image_templates']) ? ['image_templates' => $degrees_of_freedom_spec['creative_features_spec']['image_templates']] : ["image_templates" => ["enroll_status" => "OPT_IN"]],
                ...!empty($degrees_of_freedom_spec['creative_features_spec']['image_touchups']) ? ['image_touchups' => $degrees_of_freedom_spec['creative_features_spec']['image_touchups']] : ["image_touchups" => ["enroll_status" => "OPT_IN"]],
                // ...!empty($degrees_of_freedom_spec['creative_features_spec']['image_animation']) ? ['image_animation' => $degrees_of_freedom_spec['creative_features_spec']['image_animation']] : ["image_animation" => ["enroll_status" => "OPT_IN"]],
                ...!empty($degrees_of_freedom_spec['creative_features_spec']['video_auto_crop']) ? ['video_auto_crop' => $degrees_of_freedom_spec['creative_features_spec']['video_auto_crop']] : ["video_auto_crop" => ["enroll_status" => "OPT_IN"]],
                // ...!empty($degrees_of_freedom_spec['creative_features_spec']['enhance_cta']) ? ['enhance_cta' => $degrees_of_freedom_spec['creative_features_spec']['enhance_cta']] : ["enhance_cta" => ["enroll_status" => "OPT_IN", "customizations" => ["text_extraction" => ["enroll_status" => "OPT_IN"]]]],
                ...!empty($degrees_of_freedom_spec['creative_features_spec']['text_optimizations']) ? ['text_optimizations' => $degrees_of_freedom_spec['creative_features_spec']['text_optimizations']] : ["text_optimizations" => ["enroll_status" => "OPT_IN"]],
                ...!empty($degrees_of_freedom_spec['creative_features_spec']['inline_comment']) ? ['inline_comment' => $degrees_of_freedom_spec['creative_features_spec']['inline_comment']] : ['inline_comment' => ["enroll_status" => "OPT_IN"]],

                // ...!empty($degrees_of_freedom_spec['creative_features_spec']['image_brightness_and_contrast']) ? ['image_brightness_and_contrast' => $degrees_of_freedom_spec['creative_features_spec']['image_brightness_and_contrast']] : ["image_brightness_and_contrast" => ["enroll_status" => "OPT_IN"]],
                // ...!empty($degrees_of_freedom_spec['creative_features_spec']['image_background_gen']) ? ['image_background_gen' => $degrees_of_freedom_spec['creative_features_spec']['image_background_gen']] : ["image_background_gen"=>["enroll_status" => "OPT_IN"]],
                // ...!empty($degrees_of_freedom_spec['creative_features_spec']['image_uncrop']) ? ['image_uncrop' => $degrees_of_freedom_spec['creative_features_spec']['image_uncrop']] : ["image_uncrop"=>["enroll_status" => "OPT_IN"]],
                // ...!empty($degrees_of_freedom_spec['creative_features_spec']['adapt_to_placement']) ? ['adapt_to_placement' => $degrees_of_freedom_spec['creative_features_spec']['adapt_to_placement']] : ["adapt_to_placement"=>["enroll_status" => "OPT_IN"]],
                // ...!empty($degrees_of_freedom_spec['creative_features_spec']['media_type_automation']) ? ['media_type_automation' => $degrees_of_freedom_spec['creative_features_spec']['media_type_automation']] : ["media_type_automation"=>["enroll_status" => "OPT_IN"]],
                // ...!empty($degrees_of_freedom_spec['creative_features_spec']['product_extensions']) ? ['product_extensions' => $degrees_of_freedom_spec['creative_features_spec']['product_extensions']] : ["product_extensions"=>["enroll_status" => "OPT_IN"]],
            ]
        ]; */
        /* $degrees_of_freedom_spec = [
            "creative_features_spec" => [
                "image_templates" => ["enroll_status" => "OPT_OUT"],
                "image_touchups" => ["enroll_status" => "OPT_IN"],
                "text_optimizations" => ["enroll_status" => "OPT_OUT"],
                'inline_comment' => ["enroll_status" => "OPT_OUT"],
                "video_auto_crop" => ["enroll_status" => "OPT_OUT"],
            ]
        ]; */
        $degrees_of_freedom_spec = [
            "creative_features_spec" => [
                ...!empty($degrees_of_freedom_spec['creative_features_spec']['image_templates']) ? ['image_templates' => $degrees_of_freedom_spec['creative_features_spec']['image_templates']] : ["image_templates" => ["enroll_status" => "OPT_IN"]],
                ...!empty($degrees_of_freedom_spec['creative_features_spec']['image_touchups']) ? ['image_touchups' => $degrees_of_freedom_spec['creative_features_spec']['image_touchups']] : ["image_touchups" => ["enroll_status" => "OPT_IN"]],
                ...!empty($degrees_of_freedom_spec['creative_features_spec']['text_optimizations']) ? ['text_optimizations' => $degrees_of_freedom_spec['creative_features_spec']['text_optimizations']] : ["text_optimizations" => ["enroll_status" => "OPT_IN"]],
                ...!empty($degrees_of_freedom_spec['creative_features_spec']['inline_comment']) ? ['inline_comment' => $degrees_of_freedom_spec['creative_features_spec']['inline_comment']] : ['inline_comment' => ["enroll_status" => "OPT_IN"]],
                ...!empty($degrees_of_freedom_spec['creative_features_spec']['video_auto_crop']) ? ['video_auto_crop' => $degrees_of_freedom_spec['creative_features_spec']['video_auto_crop']] : ["video_auto_crop" => ["enroll_status" => "OPT_IN"]],
            ]
        ];
        // TODO: Debug
        /* $degrees_of_freedom_spec = [
            "creative_features_spec" => [
                "image_templates" => ["enroll_status" => "OPT_OUT"],
                "image_touchups" => ["enroll_status" => "OPT_OUT"],
                "text_optimizations" => ["enroll_status" => "OPT_OUT"],
                'inline_comment' => ["enroll_status" => "OPT_OUT"],
                "video_auto_crop" => ["enroll_status" => "OPT_OUT"],
            ]
        ]; */

        // TODO: Debug
        // $degrees_of_freedom_spec['creative_features_spec'] = [];

        // Remove Creative should not include standard enhancements: Including standard enhancements field in creative has been deprecated. Please choose to set individual features instead.
        unset($degrees_of_freedom_spec['creative_features_spec']['standard_enhancements']);
        if (!empty($asset_feed_spec['videos']) || !empty($object_story_spec['video_data'])) {
            unset($degrees_of_freedom_spec['creative_features_spec']['image_animation']);
            unset($degrees_of_freedom_spec['creative_features_spec']['image_brightness_and_contrast']);
            unset($degrees_of_freedom_spec['creative_features_spec']['image_templates']);
            unset($degrees_of_freedom_spec['creative_features_spec']['image_touchups']);
            unset($degrees_of_freedom_spec['creative_features_spec']['image_uncrop']);
            unset($degrees_of_freedom_spec['creative_features_spec']['image_background_gen']);
        } elseif (!empty($asset_feed_spec['images']) || !empty($object_story_spec['link_data'])) {
            unset($degrees_of_freedom_spec['creative_features_spec']['video_auto_crop']);
        }

        return $degrees_of_freedom_spec;
    }
    public static function convertObjectStorySpecToFacebookPayload($object_story_spec, $campaign)
    {
        $link_data = [];
        if (!empty($object_story_spec['link_data'])) {
            $link = !empty($campaign['storeAppInfo']['storeUrl']) ? $campaign['storeAppInfo']['storeUrl'] : $object_story_spec['link_data']['link'];
            $link_data = [
                ...!empty($object_story_spec['link_data']['name']) ? ['name' => $object_story_spec['link_data']['name']] : [],
                ...!empty($object_story_spec['link_data']['description']) ? ['description' => $object_story_spec['link_data']['description']] : [],
                ...!empty($object_story_spec['link_data']['message']) ? ['message' => $object_story_spec['link_data']['message']] : [],
                ...!empty($object_story_spec['link_data']['call_to_action']) ? ['call_to_action' => $object_story_spec['link_data']['call_to_action']] : [],
                ...!empty($object_story_spec['link_data']['image_hash']) ? ['image_hash' => $object_story_spec['link_data']['image_hash']] : [],
                // api create creative thì gọi là image_hash, còn get creative thì gọi là hash
                ...!empty($object_story_spec['link_data']['hash']) ? ['image_hash' => $object_story_spec['link_data']['hash']] : [],
                "link" => $link,
                // Custom
                ...!empty($object_story_spec['link_data']['url_need_upload']) ? ['url_need_upload' => $object_story_spec['link_data']['url_need_upload']] : [],
            ];
        }
        $video_data = [];
        if (!empty($object_story_spec['video_data'])) {
            $link = $campaign['storeAppInfo']['storeUrl'] ?? $object_story_spec['video_data']['link'];
            $video_data = [
                ...!empty($object_story_spec['video_data']['name']) ? ['name' => $object_story_spec['video_data']['name']] : [],
                ...!empty($object_story_spec['video_data']['description']) ? ['description' => $object_story_spec['video_data']['description']] : [],
                ...!empty($object_story_spec['video_data']['message']) ? ['message' => $object_story_spec['video_data']['message']] : [],
                ...!empty($object_story_spec['video_data']['call_to_action']) ? ['call_to_action' => $object_story_spec['video_data']['call_to_action']] : [],
                ...!empty($object_story_spec['video_data']['video_id']) ? ['video_id' => $object_story_spec['video_data']['video_id']] : "",
                ...!empty($object_story_spec['video_data']['image_url']) ? ['image_url' => $object_story_spec['video_data']['image_url']] : "",
                // "link" => $link,
                // Custom
                ...!empty($object_story_spec['video_data']['url_need_upload']) ? ['url_need_upload' => $object_story_spec['video_data']['url_need_upload']] : [],
            ];
        }

        $page_id = "";
        $instagram_user_id = "";
        if (!empty($campaign['pageInfo'])) {
            $page_id = $campaign['pageInfo']['pageId'];
            $instagram_user_id = $campaign['pageInfo']['instagramUserId'];
        } else {
            $page_id = $object_story_spec['page_id'] ?? "";
            $instagram_user_id = $object_story_spec['instagram_user_id'] ?? "";
        }

        $payload = [
            ...!empty($page_id) ? ['page_id' => $page_id] : [],
            ...!empty($instagram_user_id) ? ['instagram_user_id' => $instagram_user_id] : [],
            // ...!empty($campaign['pageInfo']) ? ['page_id' => $campaign['pageInfo']['pageId']] : [],
            // ...!empty($campaign['pageInfo']) ? ['instagram_user_id' => $campaign['pageInfo']['instagramUserId']] : [],
            // 'page_id' => $campaign['pageInfo']['pageId'],
            // 'instagram_user_id' => $campaign['pageInfo']['instagramUserId'],
            ...!empty($link_data) ? ['link_data' => $link_data] : [],
            ...!empty($video_data) ? ['video_data' => $video_data] : []
        ];

        return $payload;
    }

    public static function convertAssetFeedSpecToFacebookPayload($asset_feed_spec, $campaign, $object_story_spec)
    {
        if (empty($asset_feed_spec)) {
            return [];
        }
        if (empty($asset_feed_spec['link_urls'])) {
            $asset_feed_spec['link_urls'] = [];
        }
        $link_urls = !empty($campaign['storeAppInfo']['storeUrl'])
            ? array_map(function ($link) use ($campaign) {
                unset($link['deep_link']);
                $newLink = array_merge($link, [
                    'website_url' => $campaign['storeAppInfo']['storeUrl']
                ]);
                if (!empty($campaign['storeAppInfo']['deepLink'])) {
                    $newLink['deep_link'] = $campaign['storeAppInfo']['deepLink'];
                }
                return $newLink;
            }, $asset_feed_spec['link_urls'])
            : $asset_feed_spec['link_urls'];


        if (empty($asset_feed_spec['images']) && empty($asset_feed_spec['videos'])) {
            $payload = [
                ...!empty($asset_feed_spec['descriptions']) ? ['descriptions' => $asset_feed_spec['descriptions']] : [],
                ...!empty($link_urls) ? ['link_urls' => $link_urls] : [],
                ...!empty($asset_feed_spec['titles']) ? ['titles' => $asset_feed_spec['titles']] : [],
                ...!empty($asset_feed_spec['bodies']) ? ['bodies' => $asset_feed_spec['bodies']] : [],
                ...!empty($asset_feed_spec['optimization_type']) ? ['optimization_type' => $asset_feed_spec['optimization_type']] : [],
            ];
        } else {
            $payload = [
                'ad_formats' => $asset_feed_spec['ad_formats'] ?? [ "AUTOMATIC_FORMAT" ],
                'call_to_action_types' => $asset_feed_spec['call_to_action_types'] ?? [ "INSTALL_MOBILE_APP"],
                // 'descriptions' => $asset_feed_spec['descriptions'] ?? [[ "text" => ""]],
                ...!empty($asset_feed_spec['descriptions']) ? ['descriptions' => $asset_feed_spec['descriptions']] : [],
                'link_urls' => $link_urls,
                'titles' => $asset_feed_spec['titles'],
                'bodies' => $asset_feed_spec['bodies'],
                'asset_customization_rules' => $asset_feed_spec['asset_customization_rules'] ?? [],
                'optimization_type' => $asset_feed_spec['optimization_type']
            ];
        }



        if (!empty($asset_feed_spec['images'])) {
            $payload['images'] = self::convertImagesToFacebookPayload($asset_feed_spec['images'], $campaign);
            // Remove invalid image crop
            $images_label = [];
            foreach ($payload['images'] as $img) {
                if (!empty($img['adlabels'])) {
                    $images_label[] = $img['adlabels'][0]["name"];
                }
            }
            foreach ($payload["asset_customization_rules"] as $key => $rule) {
                $image_label = $rule["image_label"]["name"];
                if (!in_array($image_label, $images_label)) {
                    unset($payload["asset_customization_rules"][$key]);
                }
            }

        } elseif (!empty($asset_feed_spec['videos'])) {
            $payload['videos'] = $asset_feed_spec['videos'];
        }

        /* if (!empty($payload['titles']) || !empty($payload['bodies']) || !empty($payload['images']) || !empty($payload['videos'])) {
            $payload["optimization_type"] = "REGULAR";
        } */

        self::removeId($payload);
        return $payload;
    }
    public static function convertImagesToFacebookPayload($images, $campaign)
    {
        $imagesFormatted = [];
        foreach ($images as $image) {
            if (!empty($image['image_crops'])) {
                foreach ($image['image_crops'] as $key => $imageCrop) {
                    if ($key == '191x100') {
                        // unset($image['image_crops'][$key]);
                        continue 2; // Remove image crop 191x100
                    }
                }
            }
            $imagesFormatted[] = $image;
        }
        return $imagesFormatted;
    }

    public static function removeId(&$obj)
    {
        if (is_array($obj)) {
            if (!empty($obj['id'])) {
                unset($obj['id']);
            }

            $excludeKeys = ['ads','adsets','custom_audiences', 'creatives', 'excluded_custom_audiences', 'flexible_spec'];
            foreach ($obj as $key => &$value) {
                if (is_array($value) && !in_array($key, $excludeKeys)) {
                    self::removeId($value);
                }
            }
        }
    }
}
