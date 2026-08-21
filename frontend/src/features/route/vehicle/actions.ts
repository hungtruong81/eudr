import axiosInstance from "@/lib/axios-instance";
import { generateBaseApiUrl } from "@/lib/utils";
import {
  ApiResponse,
  ApiResponseList,
  CommonPaginationParams,
} from "@/types/api";
import { IVehicleData } from "./types";

export interface IGetVehicleParams extends CommonPaginationParams {}

export const getVehicles = async (params: IGetVehicleParams) => {
  try {
    const url = generateBaseApiUrl() + "/v1/vehicle/";
    const response = await axiosInstance.get(url, { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const getBrands = async (
  params: CommonPaginationParams,
): Promise<ApiResponse<{ vehicle_brand_name: string }[]>> => {
  try {
    const url = generateBaseApiUrl() + "/v1/vehicle/brand/";
    const response = await axiosInstance.get(url, { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const createVehicle = async (data: IVehicleData) => {
  try {
    const url = generateBaseApiUrl() + "/v1/vehicle/";
    const response = await axiosInstance.post(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const updateVehicle = async (
  vehicle_code: string,
  data: IVehicleData,
) => {
  try {
    const url = generateBaseApiUrl() + `/v1/vehicle/${vehicle_code}`;
    const response = await axiosInstance.put(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const deleteVehicle = async (vehicle_code: string) => {
  try {
    const url = generateBaseApiUrl() + `/v1/vehicle/${vehicle_code}`;
    const response = await axiosInstance.delete(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};
