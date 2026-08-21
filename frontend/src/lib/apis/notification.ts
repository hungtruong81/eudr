import { ApiResponseList, CommonPaginationParams } from "@/types/api";
import { generateBaseApiUrl } from "../utils";
import axiosInstance from "../axios-instance";
import { INotification } from "@/types/notifi";

export type NotifiStatus = {};

export interface GetNotificationParams extends CommonPaginationParams {
  related_type?: "transaction_ticket" | "connection";
  status: "all" | "read" | "unread";
}

export const ListNotification = async (
  params: GetNotificationParams
): Promise<ApiResponseList<INotification>> => {
  const url = generateBaseApiUrl() + `/v1/notification/`;

  try {
    const response = await axiosInstance.get(url, { params });

    return response.data;
  } catch (error) {
    console.log(error);
    throw error;
  }
};

export const MarkAsReadNotification = async (data: {
  notification_ids: string[];
}) => {
  const url = generateBaseApiUrl() + `/v1/notification/read/`;

  try {
    const response = await axiosInstance.put(url, data);

    return response.data;
  } catch (error) {
    console.log(error);
    throw error;
  }
};
