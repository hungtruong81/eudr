export interface ICoordinate {
  lat: number;
  lng: number;
}

export interface ICoordinateOriginPoint {
  x: number;
  y: number;
}

export interface ILandRecords {
  [key: string]: string;
}

export interface IPlot {
  plot_id: number;
  plot_code: string;
  plot_name: string;

  farmer_user_id: number;
  farmer_name: string;
  phone: string;
  email: string;

  register_type: string;
  company_name: string;
  ownership: string;

  land_records: ILandRecords;
  land_document_detection: string;

  province_id: number;
  province_name: string;
  country: string;

  coordinates: ICoordinate[];
  coordinate_origin_points: ICoordinateOriginPoint[];

  land_area: string;
  address: string;
  plant_type: string;

  altitude_above_sea_level: string;
  soil: string;
  status: string;

  maximum_yield: number;
  classify: string;

  created_at: string;
  created_by: number;
  updated_at: string | null;
  updated_by: number;

  approved_at: string;
  approved_by: number;

  area_24: string;
  notes: string;

  eudr_status: number;
  is_approved: number;

  zone_id: number;
  zone_name: string;

  crop_type: string;
  year_of_planting: number;
  plantation_name: string;
}

export interface ILandData {
  plot_name: string;

  farmer_user_id?: number;
  farmer_name: string;

  company_name: string;
  ownership: "Owner" | "Rent";

  land_records: number[];
  land_document_detection: number;

  province_id: string;
  zone_id: number;

  coordinate_origin_points: ICoordinateOriginPoint[];
  coordinates: ICoordinate[];

  land_area: string;
  address: string;

  altitude_above_sea_level: string;
  soil: string;
  status: string;

  maximum_yield: string; // API đang gửi string
  classify: string;

  area_24: string;
  notes: string;

  eudr_status: number;
  signature_file_id?: number;
}

export interface IPlotByTransactionTicket {
  plot_id: number;
  plot_code: string;
  plot_name: string;
  land_area: string;
  address: string;
  crop_type: string;
  year_of_planting: number;
  plantation_name: string;
}

export interface IListLandShareByUser {
  plot_id: number;
  plot_code: string;
  plot_name: string;
  land_area: string;
  address: string;
  share_status: string;
  crop_type: string;
  year_of_planting: number;
  plantation_name: string;
  total_shared?: string;
}
