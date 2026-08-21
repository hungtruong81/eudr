export type IProvince = {
  province_id: number;
  code: string;
  province_name: string;
  type: string;
};

export interface ApiResponseProvince {
  result: string;
  provinces: IProvince[];
}

export type Zone = {
  zone_id: number;
  zone_name: string;
  value: string;
};

export interface ApiResponseZone {
  result: string;
  zones: Zone[];
}
