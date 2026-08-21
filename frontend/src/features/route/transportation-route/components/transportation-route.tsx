"use client";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import React, { useState } from "react";
import {
  arriveTransportationRoute,
  cancelTransportationRoute,
  deleteTransportationRoute,
  getTransportationRoutes,
  IGetTransportationRouteParams,
} from "../actions";
import CustomTable, { CustomColumnTypeTable } from "@/components/custom-table";
import { ITransportationRoute } from "../types";
import { Flex, Form, message, Modal, Space, Tag, TimePicker } from "antd";
import { TooltipButton } from "@/components/tooltip-button";
import {
  CheckOutlined,
  CloseOutlined,
  DeleteOutlined,
  EditOutlined,
  PlusOutlined,
} from "@ant-design/icons";
import { TransportationRouteFilter } from "./transportation-route-filter";
import TransportationRouteForm from "./transportation-route-form";
import { handleApiError } from "@/lib/api-error";
import dayjs from "dayjs";
import { useTranslations } from "next-intl";
import { ConfirmTooltipButton } from "@/components/confirm-tooltip-button";
import { usePermissions } from "@/contexts/permission-context";

const TransportationRoute = () => {
  const tCommon = useTranslations("Common");
  const tRoute = useTranslations("Route.transportation");

  const mapStatus: Record<string, string> = {
    pending: tRoute("status_pending"),
    unloaded: tRoute("status_unloaded"),
    arrived: tRoute("status_arrived"),
    cancelled: tRoute("status_cancelled"),
  };

  const mapColor: Record<string, string> = {
    pending: "processing",
    unloaded: "warning",
    arrived: "success",
    cancelled: "error",
  };

  const [params, setParams] = useState<IGetTransportationRouteParams>({
    page: 1,
    limit: 10,
    start_date: dayjs().subtract(1, "month").format("YYYY-MM-DD"),
    end_date: dayjs().format("YYYY-MM-DD"),
  });
  const { transportationRoute } = usePermissions();
  const [filterForm] = Form.useForm();
  const [open, setOpen] = useState(false);
  const [selectedData, setSelectedData] = useState<ITransportationRoute | null>(
    null,
  );
  const queryClient = useQueryClient();

  const { data, isLoading } = useQuery({
    queryKey: ["transportation-routes", params],
    queryFn: () => getTransportationRoutes(params),
  });

  const deleteMutation = useMutation({
    mutationFn: deleteTransportationRoute,
    onSuccess: () => {
      message.success(tRoute("delete_success"));
      queryClient.invalidateQueries({ queryKey: ["transportation-routes"] });
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const cancelMutation = useMutation({
    mutationFn: cancelTransportationRoute,
    onSuccess: () => {
      message.success(tRoute("cancel_success"));
      queryClient.invalidateQueries({ queryKey: ["transportation-routes"] });
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const arriveMutation = useMutation({
    mutationFn: ({ code, time }: { code: string; time: string }) =>
      arriveTransportationRoute(code, time),
    onSuccess: () => {
      message.success(tRoute("arrive_success"));
      queryClient.invalidateQueries({ queryKey: ["transportation-routes"] });
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const handleFilter = (values: any) => {
    setParams({
      ...params,
      ...values,
      page: 1,
    });
  };

  const handleResetFilter = () => {
    filterForm.resetFields();
    setParams({
      page: 1,
      limit: 10,
      start_date: dayjs().subtract(1, "month").format("YYYY-MM-DD"),
      end_date: dayjs().format("YYYY-MM-DD"),
    });
  };

  const handleArrive = (record: ITransportationRoute) => {
    let arrivalTime = dayjs();
    Modal.confirm({
      title: tRoute("confirm_arrival"),
      content: (
        <Space orientation="vertical" style={{ width: "100%" }}>
          <p>{tRoute("select_arrival_time")}</p>
          <TimePicker
            defaultValue={dayjs()}
            format="HH:mm"
            onChange={(time) => {
              if (time) arrivalTime = time;
            }}
            style={{ width: "100%" }}
          />
        </Space>
      ),
      okText: tCommon("confirm"),
      cancelText: tCommon("cancel"),
      onOk: () =>
        arriveMutation.mutate({
          code: record.transportation_route_code,
          time: arrivalTime.format("YYYY-MM-DD HH:mm:ss"),
        }),
    });
  };

  const handleCancel = (record: ITransportationRoute) => {
    cancelMutation.mutate(record.transportation_route_code);
  };

  const handleDelete = (record: ITransportationRoute) => {
    deleteMutation.mutate(record.transportation_route_code);
  };

  const columns: CustomColumnTypeTable<ITransportationRoute>[] = [
    {
      title: tRoute("route_code"),
      dataIndex: "transportation_route_code",
      render: (val) => val?.toUpperCase(),
    },
    { title: tRoute("vehicle"), dataIndex: "vehicle_name" },
    { title: tRoute("driver"), dataIndex: "driver_name" },
    { title: tRoute("transport_date"), dataIndex: "transport_date", type: "date" },
    { title: tRoute("departure_time"), dataIndex: "pickup_time" },
    { title: tRoute("destination_factory"), dataIndex: "destination_factory_name" },
    {
      title: tRoute("status"),
      dataIndex: "status",
      render: (val) => <Tag color={mapColor[val]}>{mapStatus[val]}</Tag>,
    },
    {
      title: tRoute("actions"),
      dataIndex: "actions",
      render: (_, record) => (
        <Space>
          {record.status === "pending" && (
            <>
              {(transportationRoute.full || transportationRoute.update) && (
                <TooltipButton
                  tooltip={tRoute("confirm_arrival")}
                  icon={<CheckOutlined />}
                  type="primary"
                  onClick={() => handleArrive(record)}
                  loading={arriveMutation.isPending}
                />
              )}
              {(transportationRoute.full || transportationRoute.update) && (
                <TooltipButton
                  tooltip={tCommon("edit")}
                  icon={<EditOutlined />}
                  type="primary"
                  ghost
                  onClick={() => {
                    setSelectedData(record);
                    setOpen(true);
                  }}
                />
              )}
              {(transportationRoute.full || transportationRoute.delete) && (
                <ConfirmTooltipButton
                  confirmTitle={tRoute("confirm_cancel_title")}
                  confirmDescription={tRoute("confirm_cancel_desc", {
                    code: record.transportation_route_code?.toUpperCase(),
                  })}
                  tooltip={tRoute("status_cancelled")}
                  icon={<CloseOutlined />}
                  danger
                  onConfirm={() => handleCancel(record)}
                  loading={cancelMutation.isPending}
                />
              )}
            </>
          )}
          {(transportationRoute.full || transportationRoute.delete) && (
            <ConfirmTooltipButton
              confirmTitle={tRoute("confirm_delete_title")}
              confirmDescription={tRoute("confirm_delete_desc", {
                code: record.transportation_route_code?.toUpperCase(),
              })}
              tooltip={tCommon("delete")}
              icon={<DeleteOutlined />}
              danger
              ghost
              onConfirm={() => handleDelete(record)}
              loading={deleteMutation.isPending}
            />
          )}
        </Space>
      ),
    },
  ];

  if (open) {
    return (
      <TransportationRouteForm
        open={open}
        onOpenChange={setOpen}
        data={selectedData}
      />
    );
  }

  return (
    <Space orientation="vertical" style={{ width: "100%" }}>
      <Flex align="start" justify="space-between">
        <TransportationRouteFilter
          filterForm={filterForm}
          handleFilter={handleFilter}
          handleResetFilter={handleResetFilter}
        />
        {(transportationRoute.full || transportationRoute.create) && (
          <TooltipButton
            tooltip={tRoute("add_new")}
            icon={<PlusOutlined />}
            type="primary"
            onClick={() => {
              setSelectedData(null);
              setOpen(true);
            }}>
            {tRoute("add_new")}
          </TooltipButton>
        )}
      </Flex>
      <CustomTable<ITransportationRoute>
        rowKey="transportation_route_id"
        columns={columns}
        dataSource={data?.data?.records || []}
        loading={isLoading}
        tableId="transportation-route-table"
        pagination={{
          current: data?.data?.current_page || 1,
          pageSize: data?.data?.page_limit || 10,
          total: data?.data?.total_records || 0,
        }}
        onChange={(pagination) => {
          setParams({
            ...params,
            page: pagination.current || 1,
            limit: pagination.pageSize || 10,
          });
        }}
        scroll={{ x: "max-content" }}
      />
    </Space>
  );
};

export default TransportationRoute;
