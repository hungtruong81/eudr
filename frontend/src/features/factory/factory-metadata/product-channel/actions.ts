import axiosInstance from "@/lib/axios-instance";
import { generateBaseApiUrl } from "@/lib/utils";
import {
  ApiResponse,
  ApiResponseList,
  CommonPaginationParams,
} from "@/types/api";
import {
  IProductionChannel,
  IProductionChannelData,
  IProductionChannelRun,
} from "./types";

export interface IGetProductChannelParams extends CommonPaginationParams {
  factory_id?: number;
  channel_code?: string;
  status?: string; //available, in_use, cleaning, all
}

export interface IGetProductChannelRunParams extends CommonPaginationParams {}

export const getProductChannels = async (
  params: IGetProductChannelParams,
): Promise<ApiResponseList<IProductionChannel[]>> => {
  try {
    const url = generateBaseApiUrl() + `/v1/production-channel/`;
    const response = await axiosInstance.get(url, { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const createProductChannel = async (data: IProductionChannelData) => {
  try {
    const url = generateBaseApiUrl() + `/v1/production-channel/`;
    const response = await axiosInstance.post(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const getProductChannelByCode = async (
  channel_code: string,
): Promise<ApiResponse<IProductionChannel>> => {
  try {
    const url = generateBaseApiUrl() + `/v1/production-channel/${channel_code}`;
    const response = await axiosInstance.get(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const getProductionChannelRuns = async (
  params: IGetProductChannelRunParams,
): Promise<ApiResponseList<IProductionChannelRun[]>> => {
  try {
    const url = generateBaseApiUrl() + `/v1/production-channel/runs/`;
    const response = await axiosInstance.get(url, { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const updateProductChannel = async (
  channel_code: string,
  data: IProductionChannelData,
) => {
  try {
    const url = generateBaseApiUrl() + `/v1/production-channel/${channel_code}`;
    const response = await axiosInstance.put(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const deleteProductChannel = async (channel_code: string) => {
  try {
    const url = generateBaseApiUrl() + `/v1/production-channel/${channel_code}`;
    const response = await axiosInstance.delete(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const generateChannelCode = async (): Promise<
  ApiResponse<{ channel_code: string }>
> => {
  try {
    const url = generateBaseApiUrl() + `/v1/production-channel/generate-code/`;
    const response = await axiosInstance.get(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};
