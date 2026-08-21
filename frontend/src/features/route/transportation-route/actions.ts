import {
  ApiResponse,
  ApiResponseList,
  CommonPaginationParams,
} from "@/types/api";
import {
  ITransportationRoute,
  ITransportationRouteData,
  ITransportationRouteDetail,
} from "./types";
import { generateBaseApiUrl } from "@/lib/utils";
import axiosInstance from "@/lib/axios-instance";
import { ITransactionTicket } from "@/features/transaction-ticket/types";

export interface IGetTransportationRouteParams extends CommonPaginationParams {
  start_date?: string;
  end_date?: string;
  status?: string;
  destination_factory_id?: string;
}
export const getTransportationRoutes = async (
  params: IGetTransportationRouteParams,
): Promise<ApiResponseList<ITransportationRoute[]>> => {
  try {
    const url = generateBaseApiUrl() + "/v1/transportation-route/";
    const response = await axiosInstance.get(url, { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const createTransportationRoute = async (
  data: ITransportationRouteData,
) => {
  try {
    const url = generateBaseApiUrl() + "/v1/transportation-route/";
    const response = await axiosInstance.post(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const updateTransportationRoute = async (
  transportation_route_code: string,
  data: ITransportationRouteData,
) => {
  try {
    const url =
      generateBaseApiUrl() +
      `/v1/transportation-route/${transportation_route_code}`;
    const response = await axiosInstance.put(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const arriveTransportationRoute = async (
  transportation_route_code: string,
  arrival_time: string,
) => {
  try {
    const url =
      generateBaseApiUrl() +
      `/v1/transportation-route/${transportation_route_code}/arrive/`;
    const response = await axiosInstance.put(url, { arrival_time });
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const cancelTransportationRoute = async (
  transportation_route_code: string,
) => {
  try {
    const url =
      generateBaseApiUrl() +
      `/v1/transportation-route/${transportation_route_code}/cancel/`;
    const response = await axiosInstance.put(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const deleteTransportationRoute = async (
  transportation_route_code: string,
) => {
  try {
    const url =
      generateBaseApiUrl() +
      `/v1/transportation-route/${transportation_route_code}`;
    const response = await axiosInstance.delete(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const getPurchaseTicketUnrouted = async (
  params: CommonPaginationParams,
): Promise<ApiResponseList<ITransactionTicket[]>> => {
  try {
    const url =
      generateBaseApiUrl() + `/v1/transaction-ticket/purchase-ticket/unrouted/`;
    const response = await axiosInstance.get(url, { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const getTransportationRouteByCode = async (
  transportation_route_code: string,
): Promise<ApiResponse<ITransportationRouteDetail>> => {
  try {
    const url =
      generateBaseApiUrl() +
      `/v1/transportation-route/${transportation_route_code}`;
    const response = await axiosInstance.get(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};
