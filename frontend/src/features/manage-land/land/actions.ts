import axiosInstance from "@/lib/axios-instance";
import { generateBaseApiUrl } from "@/lib/utils";
import { ApiResponseList, CommonPaginationParams } from "@/types/api";
import { ILandData, IPlot, IPlotByTransactionTicket } from "./types";

export interface IGetLandParams extends CommonPaginationParams {
  is_approved?: number;
  farmer_user_id?: number;
  not_shared_with_user_id?: number;
  province_id?: number;
  eudr_status?: 0 | 1 | 2;
}

export interface IGetLandByTransactionTicketParams extends CommonPaginationParams {
  transaction_ticket_code: string;
}

export const getLands = async (
  params: Partial<IGetLandParams>,
): Promise<ApiResponseList<IPlot[]>> => {
  try {
    const url = generateBaseApiUrl() + `/v1/land/`;
    const response = await axiosInstance.get(url, { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};
export const getLandById = async (id: string): Promise<{ data: IPlot }> => {
  const url = generateBaseApiUrl() + `/v1/land/${id}`;
  try {
    const response = await axiosInstance.get(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const getLandByTransactionTicket = async (
  params: Partial<IGetLandByTransactionTicketParams>,
): Promise<ApiResponseList<IPlotByTransactionTicket[]>> => {
  try {
    const url = generateBaseApiUrl() + `/v1/land/by-transaction-ticket/`;
    const response = await axiosInstance.get(url, { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const createLand = async (
  data: Partial<ILandData>,
): Promise<{ result: string }> => {
  const url = generateBaseApiUrl() + "/v1/land/";

  try {
    const response = await axiosInstance.post(url, data);

    return response.data;
  } catch (error) {
    throw error;
  }
};

export const updateLand = async (
  data: Partial<ILandData>,
  plot_code: string,
): Promise<{ result: string }> => {
  const url = generateBaseApiUrl() + `/v1/land/${plot_code}`;

  try {
    const response = await axiosInstance.put(url, data);

    return response.data;
  } catch (error) {
    throw error;
  }
};

export const approveLand = async (
  land_code: string,
  eudr_status: number,
  is_approved: number,
) => {
  const url = generateBaseApiUrl() + `/v1/land/approve/${land_code}`;
  try {
    const response = await axiosInstance.put(url, {
      eudr_status,
      is_approved,
    });

    return response.data;
  } catch (error) {
    throw error;
  }
};

export const deleteLand = async (plot_code: string) => {
  const url = generateBaseApiUrl() + `/v1/land/${plot_code}`;
  try {
    const response = await axiosInstance.delete(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};
