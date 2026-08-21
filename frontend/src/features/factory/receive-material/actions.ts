import axiosInstance from "@/lib/axios-instance";
import { generateBaseApiUrl } from "@/lib/utils";

export interface IUnloadingItem {
  raw_material_tank_id: number;
  rubber_type: "latex" | "scrap_rubber";
  actual_weight: string;
}

export interface IUnloadingItemsResponse {
  unloading_items: IUnloadingItem[];
}

export const transportationRouteUnload = async (
  transportation_route_code: string,
  data: IUnloadingItemsResponse,
) => {
  try {
    const url =
      generateBaseApiUrl() +
      `/v1/transportation-route/${transportation_route_code}/unload/`;
    const response = await axiosInstance.post(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};
