import axiosInstance from "@/lib/axios-instance";
import { generateBaseApiUrl } from "@/lib/utils";
import {
  ApiResponse,
  ApiResponseList,
  CommonPaginationParams,
} from "@/types/api";
import { ICutting, ICuttingData } from "./types";

export interface IGetCuttingMachinesParams extends CommonPaginationParams {
  factory_id?: number;
  status?: string; //available,in_use,maintenance,all
}

export const getCuttingMachines = async (
  params: IGetCuttingMachinesParams,
): Promise<ApiResponseList<ICutting[]>> => {
  try {
    const url = generateBaseApiUrl() + `/v1/production-cutting-machine/`;
    const response = await axiosInstance.get(url, { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const getCuttingMachineByCode = async (
  cutting_machine_code: string,
): Promise<ApiResponse<ICutting>> => {
  try {
    const url =
      generateBaseApiUrl() +
      `/v1/production-cutting-machine/${cutting_machine_code}`;
    const response = await axiosInstance.get(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const createCuttingMachine = async (data: ICuttingData) => {
  try {
    const url = generateBaseApiUrl() + `/v1/production-cutting-machine/`;
    const response = await axiosInstance.post(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const updateCuttingMachine = async (
  cutting_machine_code: string,
  data: ICuttingData,
) => {
  try {
    const url =
      generateBaseApiUrl() +
      `/v1/production-cutting-machine/${cutting_machine_code}`;
    const response = await axiosInstance.put(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const deleteCuttingMachine = async (cutting_machine_code: string) => {
  try {
    const url =
      generateBaseApiUrl() +
      `/v1/production-cutting-machine/${cutting_machine_code}`;
    const response = await axiosInstance.delete(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const generateCode = async (): Promise<
  ApiResponse<{ cutting_machine_code: string }>
> => {
  try {
    const url =
      generateBaseApiUrl() + `/v1/production-cutting-machine/generate-code/`;
    const response = await axiosInstance.get(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};
