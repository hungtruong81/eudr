import axiosInstance from "@/lib/axios-instance";
import { generateBaseApiUrl } from "@/lib/utils";
import { ApiResponseList, CommonPaginationParams } from "@/types/api";
import {
  IHistoryRawMaterialTank,
  IRawMaterialTank,
  IRawMaterialTankData,
} from "./types";

export interface IGetRawMaterialTankParams extends CommonPaginationParams {
  factory_id?: number;
  tank_type?: "latex" | "scrap_rubber" | "mixed";
}

export const getRawMaterialTank = async (
  params: IGetRawMaterialTankParams,
): Promise<ApiResponseList<IRawMaterialTank[]>> => {
  try {
    const url = generateBaseApiUrl() + `/v1/raw-material-tank/`;
    const response = await axiosInstance.get(url, { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const createRawMaterialTank = async (data: IRawMaterialTankData) => {
  try {
    const url = generateBaseApiUrl() + `/v1/raw-material-tank/`;
    const response = await axiosInstance.post(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const updateRawMaterialTank = async (
  raw_material_tank_code: string,
  data: IRawMaterialTankData,
) => {
  try {
    const url =
      generateBaseApiUrl() + `/v1/raw-material-tank/${raw_material_tank_code}`;
    const response = await axiosInstance.put(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const deleteRawMaterialTank = async (raw_material_tank_code: string) => {
  try {
    const url =
      generateBaseApiUrl() + `/v1/raw-material-tank/${raw_material_tank_code}`;
    const response = await axiosInstance.delete(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const historyRawMaterialTank = async (
  raw_material_tank_code: string,
  params: CommonPaginationParams,
): Promise<ApiResponseList<IHistoryRawMaterialTank[]>> => {
  try {
    const url =
      generateBaseApiUrl() +
      `/v1/raw-material-tank/${raw_material_tank_code}/history/`;
    const response = await axiosInstance.get(url, { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};
