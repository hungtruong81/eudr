import axiosInstance from "@/lib/axios-instance";
import { generateBaseApiUrl } from "@/lib/utils";
import {
  ApiResponse,
  ApiResponseList,
  CommonPaginationParams,
} from "@/types/api";
import { IPallet, IPalletData, IPalletItem } from "./types";

export interface IGetPalletParams extends CommonPaginationParams {}
export interface IGetPalletItemsParams extends CommonPaginationParams {
  pallet_code: string;
}

export const generatePalletCode = async (): Promise<
  ApiResponse<{ pallet_code: string }>
> => {
  try {
    const url = generateBaseApiUrl() + `/v1/pallet/generate-code/`;
    const response = await axiosInstance.get(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const createPallet = async (data: IPalletData) => {
  try {
    const url = generateBaseApiUrl() + `/v1/pallet/`;
    const response = await axiosInstance.post(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const addPalletItem = async (
  pallet_code: string,
  data: { rubber_block_ids: string[] },
) => {
  try {
    const url = generateBaseApiUrl() + `/v1/pallet/${pallet_code}/items/`;
    const response = await axiosInstance.post(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const getPallet = async (
  params: IGetPalletParams,
): Promise<ApiResponseList<IPallet[]>> => {
  try {
    const url = generateBaseApiUrl() + `/v1/pallet/`;
    const response = await axiosInstance.get(url, { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const getPalletItems = async (
  params: IGetPalletItemsParams,
): Promise<ApiResponse<{ pallet: IPallet; items: IPalletItem[] }>> => {
  try {
    const url =
      generateBaseApiUrl() + `/v1/pallet/${params.pallet_code}/items/`;
    const response = await axiosInstance.get(url, { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const getPalletByCode = async (
  pallet_code: string,
): Promise<ApiResponse<IPallet>> => {
  try {
    const url = generateBaseApiUrl() + `/v1/pallet/${pallet_code}`;
    const response = await axiosInstance.get(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const cancelPallet = async (pallet_code: string) => {
  try {
    const url = generateBaseApiUrl() + `/v1/pallet/${pallet_code}/cancel/`;
    const response = await axiosInstance.put(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const deletePallet = async (pallet_code: string) => {
  try {
    const url = generateBaseApiUrl() + `/v1/pallet/${pallet_code}`;
    const response = await axiosInstance.delete(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const deletePalletItems = async (
  pallet_code: string,
  pallet_item_id: number,
) => {
  try {
    const url =
      generateBaseApiUrl() +
      `/v1/pallet/${pallet_code}/items/${pallet_item_id}`;
    const response = await axiosInstance.delete(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};
