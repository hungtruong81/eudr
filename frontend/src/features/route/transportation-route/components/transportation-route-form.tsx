"use client";
import React, { useEffect } from "react";
import { ITransportationRoute } from "../types";
import {
  Button,
  Card,
  Col,
  DatePicker,
  Divider,
  Flex,
  Form,
  Input,
  message,
  Row,
  Spin,
  TimePicker,
} from "antd";
import { useForm } from "antd/es/form/Form";
import { InfiniteScrollSelect } from "@/components/infinite-scroll";
import { getVehicles } from "../../vehicle/actions";
import { IVehicle } from "../../vehicle/types";
import { ITransactionTicket } from "@/features/transaction-ticket/types";
import {
  createTransportationRoute,
  getPurchaseTicketUnrouted,
  updateTransportationRoute,
} from "../actions";
import { getFactory } from "@/features/factory/factory-metadata/factory/actions";
import { IFactory } from "@/features/factory/factory-metadata/factory/types";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { handleApiError } from "@/lib/api-error";
import dayjs from "dayjs";

import { useTranslations } from "next-intl";

export interface ITransportationRouteFormProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  data?: ITransportationRoute | null;
}

const TransportationRouteForm = ({
  open,
  onOpenChange,
  data,
}: ITransportationRouteFormProps) => {
  const [form] = useForm();
  const queryClient = useQueryClient();
  const tRoute = useTranslations("Route.transportation");

  const createMutation = useMutation({
    mutationFn: createTransportationRoute,
    onSuccess: () => {
      message.success(tRoute("create_success"));
      queryClient.invalidateQueries({ queryKey: ["transportation-routes"] });
      onOpenChange(false);
      form.resetFields();
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const updateMutation = useMutation({
    mutationFn: ({ code, data }: { code: string; data: any }) =>
      updateTransportationRoute(code, data),
    onSuccess: () => {
      message.success(tRoute("update_success"));
      queryClient.invalidateQueries({ queryKey: ["transportation-routes"] });
      onOpenChange(false);
      form.resetFields();
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  useEffect(() => {
    if (data) {
      form.setFieldsValue({
        ...data,
        transport_date: data.transport_date ? dayjs(data.transport_date) : null,
        pickup_time: data.pickup_time ? dayjs(data.pickup_time, "HH:mm") : null,
        source_transaction_ticket_ids: data.source_transaction_tickets?.map(
          (item: any) => item.transaction_ticket_id.toString(),
        ),
        vehicle_id: data.vehicle_id.toString(),
        destination_factory_id: data.destination_factory_id.toString(),
      });
    } else {
      form.resetFields();
    }
  }, [data, form]);

  const onFinish = (values: any) => {
    const payload = {
      ...values,
      transport_date: values.transport_date?.format("YYYY-MM-DD"),
      pickup_time: values.pickup_time?.format("HH:mm"),
      source_transaction_ticket_ids: values.source_transaction_ticket_ids.map(
        (id: string) => Number(id),
      ),
      vehicle_id: Number(values.vehicle_id),
      destination_factory_id: Number(values.destination_factory_id),
      source_type: "purchase_ticket",
    };

    if (data) {
      updateMutation.mutate({
        code: data.transportation_route_code,
        data: payload,
      });
    } else {
      createMutation.mutate(payload);
    }
  };

  return (
    <Spin spinning={createMutation.isPending || updateMutation.isPending}>
      <Card title={data ? tRoute("edit_title") : tRoute("create_title")}>
        <Form form={form} layout="vertical" onFinish={onFinish}>
          <Row gutter={16}>
            <Col span={24} md={12}>
              <Form.Item
                label={tRoute("vehicle")}
                name="vehicle_id"
                rules={[{ required: true, message: tRoute("select_vehicle") }]}>
                <InfiniteScrollSelect<IVehicle>
                  queryKey={["vehicle"]}
                  fetchFn={getVehicles}
                  mapOption={(item: IVehicle) => ({
                    label: item.vehicle_name,
                    value: item.vehicle_id.toString(),
                  })}
                  placeholder={tRoute("select_vehicle")}
                />
              </Form.Item>
            </Col>
            <Col span={24} md={12}>
              <Form.Item
                label={tRoute("driver")}
                name="driver_name"
                rules={[
                  { required: true, message: tRoute("enter_driver_name") },
                ]}>
                <Input placeholder={tRoute("enter_driver_name")} />
              </Form.Item>
            </Col>
          </Row>

          <Row gutter={16}>
            <Col span={24} md={12}>
              <Form.Item
                label={tRoute("transport_date")}
                name="transport_date"
                rules={[
                  { required: true, message: tRoute("select_transport_date") },
                ]}>
                <DatePicker
                  placeholder={tRoute("select_transport_date")}
                  style={{ width: "100%" }}
                  format={["DD/MM/YYYY", "YYYY-MM-DD"]}
                />
              </Form.Item>
            </Col>
            <Col span={24} md={12}>
              <Form.Item
                label={tRoute("pickup_time")}
                name="pickup_time"
                rules={[
                  { required: true, message: tRoute("select_pickup_time") },
                ]}>
                <TimePicker
                  placeholder={tRoute("select_pickup_time")}
                  style={{ width: "100%" }}
                  format="HH:mm"
                />
              </Form.Item>
            </Col>
          </Row>

          <Form.Item
            label={tRoute("select_purchase_tickets")}
            name="source_transaction_ticket_ids"
            rules={[
              {
                required: true,
                message: tRoute("select_purchase_tickets"),
              },
            ]}>
            <InfiniteScrollSelect<ITransactionTicket>
              queryKey={["purchase-tickets", "unrouted"]}
              fetchFn={getPurchaseTicketUnrouted}
              mapOption={(item: ITransactionTicket) => ({
                label: `${item?.contract_code?.toUpperCase()} - ${item?.seller_name} - ${item.seller_account_type} - ${item?.seller_address}`,
                value: item.transaction_ticket_id.toString(),
              })}
              mode="multiple"
              placeholder={tRoute("select_purchase_tickets")}
            />
          </Form.Item>

          <Form.Item
            label={tRoute("destination_factory")}
            name="destination_factory_id"
            rules={[
              {
                required: true,
                message: tRoute("select_destination_factory"),
              },
            ]}>
            <InfiniteScrollSelect<IFactory>
              queryKey={["factories"]}
              fetchFn={getFactory}
              mapOption={(item: IFactory) => ({
                label: item.factory_name,
                value: item.factory_id.toString(),
              })}
              placeholder={tRoute("select_destination_factory")}
            />
          </Form.Item>

          <Divider />
          <Flex justify="flex-end" gap="small">
            <Button onClick={() => onOpenChange(false)}>{tRoute("status_cancelled")}</Button>
            <Button
              type="primary"
              htmlType="submit"
              loading={createMutation.isPending || updateMutation.isPending}>
              {data ? tRoute("update_btn") : tRoute("create_btn")}
            </Button>
          </Flex>
        </Form>
      </Card>
    </Spin>
  );
};

export default TransportationRouteForm;
