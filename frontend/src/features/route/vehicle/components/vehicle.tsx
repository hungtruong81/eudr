"use client";
import React, { useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Flex, Form, message, Modal, Space } from "antd";
import { DeleteOutlined, EditOutlined, PlusOutlined } from "@ant-design/icons";
import { getVehicles, deleteVehicle, IGetVehicleParams } from "../actions";
import { IVehicle } from "../types";
import CustomTable, { CustomColumnTypeTable } from "@/components/custom-table";
import { TooltipButton } from "@/components/tooltip-button";
import { VehicleFilter } from "./vehicle-filter";
import { VehicleSheet } from "./vehicle-sheet";
import { handleApiError } from "@/lib/api-error";
import { ConfirmTooltipButton } from "@/components/confirm-tooltip-button";
import { usePermissions } from "@/contexts/permission-context";

import { useTranslations } from "next-intl";

const Vehicle = () => {
  const tCommon = useTranslations("Common");
  const tVehicle = useTranslations("Route.vehicle");

  const [params, setParams] = useState<IGetVehicleParams>({
    page: 1,
    limit: 10,
  });
  const { vehicle } = usePermissions();
  const [open, setOpen] = useState(false);
  const [selectedRecord, setSelectedRecord] = useState<IVehicle | null>(null);
  const [filterForm] = Form.useForm();
  const queryClient = useQueryClient();

  const { data, isLoading } = useQuery({
    queryKey: ["vehicles", params],
    queryFn: () => getVehicles(params),
  });

  const deleteMutation = useMutation({
    mutationFn: deleteVehicle,
    onSuccess: () => {
      message.success(tVehicle("delete_success"));
      queryClient.invalidateQueries({ queryKey: ["vehicles"] });
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const handleEdit = (record: IVehicle) => {
    setSelectedRecord(record);
    setOpen(true);
  };

  const handleDelete = (record: IVehicle) => {
    deleteMutation.mutate(record.vehicle_code);
  };

  const handleFilter = (values: any) => {
    setParams({
      ...params,
      ...values,
      page: 1,
    });
  };

  const handleResetFilter = () => {
    setParams({
      page: 1,
      limit: 10,
    });
  };

  const columns: CustomColumnTypeTable<IVehicle>[] = [
    {
      title: tVehicle("vehicle_name"),
      dataIndex: "vehicle_name",
    },
    {
      title: tVehicle("license_plate"),
      dataIndex: "license_plate",
    },
    {
      title: tVehicle("brand"),
      dataIndex: "brand",
    },
    {
      title: tVehicle("type"),
      dataIndex: "type",
    },
    {
      title: tVehicle("manufacture_year"),
      dataIndex: "manufacture_year",
    },
    {
      title: tVehicle("created_at"),
      dataIndex: "created_at",
      render: (date) =>
        date ? new Date(date).toLocaleDateString("vi-VN") : "-",
    },
    {
      title: tCommon("actions"),
      dataIndex: "actions",
      render: (_, record) => (
        <Space>
          {(vehicle.full || vehicle.update) && (
            <TooltipButton
              tooltip={tCommon("edit")}
              type="primary"
              ghost
              icon={<EditOutlined />}
              onClick={() => handleEdit(record)}
            />
          )}
          {(vehicle.full || vehicle.delete) && (
            <ConfirmTooltipButton
              confirmTitle={tCommon("confirm_delete")}
              confirmDescription={tVehicle("confirm_delete_desc", {
                name: record.vehicle_name,
                plate: record.license_plate,
              })}
              tooltip={tCommon("delete")}
              danger
              icon={<DeleteOutlined />}
              onConfirm={() => handleDelete(record)}
              loading={
                deleteMutation.isPending &&
                deleteMutation.variables === record.vehicle_code
              }
            />
          )}
        </Space>
      ),
    },
  ];

  return (
    <Space orientation="vertical" style={{ width: "100%" }}>
      <Flex justify="space-between" align="start">
        <VehicleFilter
          filterForm={filterForm}
          handleFilter={handleFilter}
          handleResetFilter={handleResetFilter}
        />
        {(vehicle.full || vehicle.create) && (
          <TooltipButton
            tooltip={tVehicle("add_vehicle")}
            type="primary"
            icon={<PlusOutlined />}
            onClick={() => {
              setSelectedRecord(null);
              setOpen(true);
            }}>
            {tVehicle("add_new")}
          </TooltipButton>
        )}
      </Flex>

      <CustomTable<IVehicle>
        rowKey="vehicle_id"
        columns={columns}
        dataSource={data?.data?.records || []}
        loading={isLoading}
        tableId="vehicle-table"
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

      <VehicleSheet
        open={open}
        onClose={() => setOpen(false)}
        record={selectedRecord}
      />
    </Space>
  );
};

export default Vehicle;
