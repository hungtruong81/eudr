import axiosInstance from "@/lib/axios-instance";
import { generateBaseApiUrl } from "@/lib/utils";
import {
  ApiResponse,
  ApiResponseList,
  CommonPaginationParams,
} from "@/types/api";
import { IExternalMaterial, IExternalMaterialData } from "./types";

export interface IGetExternalMaterialParams extends CommonPaginationParams {
  status?: "all" | "draft" | "confirmed" | "cancelled";
  start_date?: string;
  end_date?: string;
  factory_id?: number;
}

export const getExternalMaterial = async (
  params: IGetExternalMaterialParams,
): Promise<ApiResponseList<IExternalMaterial>> => {
  try {
    const url = generateBaseApiUrl() + `/v1/external-material/`;
    const response = await axiosInstance.get(url, { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const createExternalMaterial = async (data: IExternalMaterialData) => {
  try {
    const url = generateBaseApiUrl() + `/v1/external-material/`;
    const response = await axiosInstance.post(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const updateExternalMaterial = async (
  external_material_code: string,
  data: IExternalMaterialData,
) => {
  try {
    const url =
      generateBaseApiUrl() + `/v1/external-material/${external_material_code}`;
    const response = await axiosInstance.put(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const getExternalMaterialByCode = async (
  external_material_code: string,
): Promise<ApiResponse<IExternalMaterial>> => {
  try {
    const url =
      generateBaseApiUrl() + `/v1/external-material/${external_material_code}`;
    const response = await axiosInstance.get(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const confirmExternalMaterial = async (
  external_material_code: string,
) => {
  try {
    const url =
      generateBaseApiUrl() +
      `/v1/external-material/${external_material_code}/confirm`;
    const response = await axiosInstance.put(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const cancelExternalMaterial = async (
  external_material_code: string,
) => {
  try {
    const url =
      generateBaseApiUrl() +
      `/v1/external-material/${external_material_code}/cancel`;
    const response = await axiosInstance.put(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};
