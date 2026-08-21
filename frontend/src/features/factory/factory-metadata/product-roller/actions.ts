import { generateBaseApiUrl } from "@/lib/utils";
import axiosInstance from "@/lib/axios-instance";
import { ApiResponse, ApiResponseList } from "@/types/api";
import { IProductionRoller } from "./types";
import { CommonPaginationParams } from "@/types/api";

interface IGetProductionRollersParams extends CommonPaginationParams {
  factory_id?: number;
  status?: string; //available,in_use,maintenance,all
}

export const getProductionRollers = async (
  params: IGetProductionRollersParams,
): Promise<ApiResponseList<IProductionRoller[]>> => {
  try {
    const url = generateBaseApiUrl() + `/v1/production-roller/`;
    const response = await axiosInstance.get(url, { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const getProductionRollerByCode = async (
  roller_code: string,
): Promise<ApiResponse<IProductionRoller>> => {
  try {
    const url = generateBaseApiUrl() + `/v1/production-roller/${roller_code}`;
    const response = await axiosInstance.get(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const createProductionRoller = async (
  data: IProductionRoller,
): Promise<ApiResponse<IProductionRoller>> => {
  try {
    const url = generateBaseApiUrl() + `/v1/production-roller/`;
    const response = await axiosInstance.post(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const updateProductionRoller = async (
  roller_code: string,
  data: IProductionRoller,
) => {
  try {
    const url = generateBaseApiUrl() + `/v1/production-roller/${roller_code}`;
    const response = await axiosInstance.patch(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const deleteProductionRoller = async (roller_code: string) => {
  try {
    const url = generateBaseApiUrl() + `/v1/production-roller/${roller_code}`;
    const response = await axiosInstance.delete(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const generateProductionRollerCode = async (): Promise<
  ApiResponse<{ roller_code: string }>
> => {
  try {
    const url = generateBaseApiUrl() + `/v1/production-roller/generate-code/`;
    const response = await axiosInstance.get(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};
