import axiosInstance from "@/lib/axios-instance";
import { generateBaseApiUrl } from "@/lib/utils";
import {
  ApiResponse,
  ApiResponseList,
  CommonPaginationParams,
} from "@/types/api";
import {
  IRawMaterialRelease,
  IRawMaterialReleaseByCode,
  IRawMaterialReleaseData,
} from "./types";

export interface IGerRawMaterialReleaseParams extends CommonPaginationParams {
  status?: string;
  created_date_from: string;
  created_date_to: string;
}
export const getRawMaterialRelease = async (
  params: Partial<IGerRawMaterialReleaseParams>,
): Promise<ApiResponseList<IRawMaterialRelease[]>> => {
  try {
    const url = generateBaseApiUrl() + `/v1/raw-material-release/`;
    const response = await axiosInstance.get(url, { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const getRawMaterialReleaseById = async (
  material_release_code: string,
): Promise<ApiResponse<IRawMaterialReleaseByCode>> => {
  try {
    const url =
      generateBaseApiUrl() +
      `/v1/raw-material-release/${material_release_code}`;
    const response = await axiosInstance.get(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const createRawMaterialRelease = async (
  data: IRawMaterialReleaseData,
) => {
  try {
    const url = generateBaseApiUrl() + `/v1/raw-material-release/`;
    const response = await axiosInstance.post(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const updateRawMaterialRelease = async (
  material_release_code: string,
  data: IRawMaterialReleaseData,
) => {
  try {
    const url =
      generateBaseApiUrl() +
      `/v1/raw-material-release/${material_release_code}`;
    const response = await axiosInstance.put(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const deleteRawMaterialRelease = async (
  material_release_code: string,
) => {
  try {
    const url =
      generateBaseApiUrl() +
      `/v1/raw-material-release/${material_release_code}`;
    const response = await axiosInstance.delete(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const generateCodeRawMaterialRelease = async () => {
  try {
    const url =
      generateBaseApiUrl() + `/v1/raw-material-release/generate-code/`;
    const response = await axiosInstance.get(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};
