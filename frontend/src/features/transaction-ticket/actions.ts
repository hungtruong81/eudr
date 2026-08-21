import axiosInstance from "@/lib/axios-instance";
import { generateBaseApiUrl } from "@/lib/utils";
import {
  ApiResponse,
  ApiResponseList,
  CommonPaginationParams,
} from "@/types/api";
import { ITransactionTicket, ITransactionTicketPayload } from "./types";

export interface IGetTransactionTicketParams extends CommonPaginationParams {
  transaction_ticket_type: "sale" | "purchase";
  status: string;
  sales_source?: "land" | "ticket";
  start_date: string;
  end_date: string;
  account_type: string;
  contract_code?: string;
  member_user_id?: number;
}
export const getTransactionTikets = async (
  params: Partial<IGetTransactionTicketParams>,
): Promise<ApiResponseList<ITransactionTicket[]>> => {
  try {
    const url = generateBaseApiUrl() + `/v1/transaction-ticket/`;
    const response = await axiosInstance.get(url, { params });
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const createTransactionTicket = async (
  payload: ITransactionTicketPayload,
) => {
  try {
    const url = generateBaseApiUrl() + `/v1/transaction-ticket/`;
    const response = await axiosInstance.post(url, payload);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const updateTransactionTicket = async (
  transaction_ticket_code: string,
  payload: Partial<ITransactionTicketPayload>,
) => {
  try {
    const url =
      generateBaseApiUrl() +
      `/v1/transaction-ticket/${transaction_ticket_code}`;
    const response = await axiosInstance.put(url, payload);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const confirmTransactionTicket = async (data: {
  transaction_ticket_type: "purchase" | "sale";
  transaction_ticket_code: string;
}) => {
  try {
    const url = generateBaseApiUrl() + `/v1/transaction-ticket/confirm/`;
    const response = await axiosInstance.post(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const cancelTransactionTicket = async (data: {
  transaction_ticket_type: "purchase" | "sale";
  transaction_ticket_code: string;
}) => {
  try {
    const url = generateBaseApiUrl() + `/v1/transaction-ticket/cancel/`;
    const response = await axiosInstance.post(url, data);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const getTransactionTicketDetail = async (
  transaction_ticket_code: string,
  transaction_ticket_type: "sale" | "purchase",
): Promise<ApiResponse<ITransactionTicket>> => {
  try {
    const url =
      generateBaseApiUrl() +
      `/v1/transaction-ticket/${transaction_ticket_code}?transaction_ticket_type=${transaction_ticket_type}`;
    const response = await axiosInstance.get(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};

export const getTicketByContractCode = async (
  contract_code: string,
): Promise<ApiResponse<ITransactionTicket>> => {
  try {
    const url =
      generateBaseApiUrl() + `/v1/transaction-ticket/contract/${contract_code}`;
    const response = await axiosInstance.get(url);
    return response.data;
  } catch (error) {
    throw error;
  }
};
