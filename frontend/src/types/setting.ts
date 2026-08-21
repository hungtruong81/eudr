export interface ISettingItem {
  setting_code:
    | "latex_price_per_tsc_kg"
    | "scrap_rubber_price_per_drc_kg"
    | "show_e_signature_box_land"
    | "show_e_signature_box_plant"
    | "show_e_signature_box_import_product_lot";
  comment: string;
  value: string;
}

export interface SettingSuccessResponse {
  result: "success";
  data: ISettingItem[];
  trace_id?: string;
}

export interface SettingError {
  code: string;
  description: string;
}

export interface SettingFailureResponse {
  result: "fail";
  error: SettingError;
  trace_id: string;
}

export type SettingResponse = SettingSuccessResponse | SettingFailureResponse;
