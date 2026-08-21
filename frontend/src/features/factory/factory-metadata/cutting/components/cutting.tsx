"use client";
import React, { useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Flex, Form, message, Space, Tag } from "antd";
import { DeleteOutlined, EditOutlined, PlusOutlined } from "@ant-design/icons";
import {
  getCuttingMachines,
  deleteCuttingMachine,
  IGetCuttingMachinesParams,
} from "../actions";
import { ICutting } from "../types";
import CustomTable, { CustomColumnTypeTable } from "@/components/custom-table";
import { TooltipButton } from "@/components/tooltip-button";
import { CuttingFilter } from "./cutting-filter";
import { CuttingSheet } from "./cutting-sheet";
import { handleApiError } from "@/lib/api-error";
import { ConfirmTooltipButton } from "@/components/confirm-tooltip-button";
import { usePermissions } from "@/contexts/permission-context";
import { useTranslations } from "next-intl";

export const Cutting = () => {
  const t = useTranslations("Factory.metadata.cutting");
  const tc = useTranslations("Common");

  const [params, setParams] = useState<IGetCuttingMachinesParams>({
    page: 1,
    limit: 10,
  });
  const { factory } = usePermissions();
  const [open, setOpen] = useState(false);
  const [selectedRecord, setSelectedRecord] = useState<ICutting | null>(null);
  const [filterForm] = Form.useForm();
  const queryClient = useQueryClient();

  const { data, isLoading } = useQuery({
    queryKey: ["cutting-machines", params],
    queryFn: () => getCuttingMachines(params),
  });

  const deleteMutation = useMutation({
    mutationFn: deleteCuttingMachine,
    onSuccess: () => {
      message.success(t("delete_success"));
      queryClient.invalidateQueries({ queryKey: ["cutting-machines"] });
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const handleEdit = (record: ICutting) => {
    setSelectedRecord(record);
    setOpen(true);
  };

  const handleDelete = (record: ICutting) => {
    deleteMutation.mutate(record.cutting_machine_code);
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

  const columns: CustomColumnTypeTable<ICutting>[] = [
    {
      title: t("name"),
      dataIndex: "cutting_machine_name",
    },
    {
      title: t("code"),
      dataIndex: "cutting_machine_code",
      render: (text) => text?.toUpperCase(),
    },
    {
      title: t("factory"),
      dataIndex: "factory_name",
    },
    {
      title: t("status"),
      dataIndex: "status",
      render: (status: string) => {
        let color = "default";
        let label = status;
        if (status === "available") {
          color = "success";
          label = t("status_available");
        } else if (status === "in_use") {
          color = "processing";
          label = t("status_in_use");
        } else if (status === "maintenance") {
          color = "warning";
          label = t("status_maintenance");
        }
        return <Tag color={color}>{label}</Tag>;
      },
    },
    {
      title: tc("created_at"),
      dataIndex: "created_at",
      type: "date",
    },
    {
      title: tc("actions"),
      dataIndex: "actions",
      render: (_, record) => (
        <Space>
          {(factory.full || factory.update) && (
            <TooltipButton
              tooltip={t("edit_tooltip")}
              type="primary"
              ghost
              icon={<EditOutlined />}
              onClick={() => handleEdit(record)}
            />
          )}
          {(factory.full || factory.delete) && (
            <ConfirmTooltipButton
              confirmTitle={t("confirm_delete_title")}
              confirmDescription={t("confirm_delete_desc", {
                name: record.cutting_machine_name,
              })}
              tooltip={t("delete_tooltip")}
              danger
              icon={<DeleteOutlined />}
              onConfirm={() => handleDelete(record)}
              loading={
                deleteMutation.isPending &&
                deleteMutation.variables === record.cutting_machine_code
              }
            />
          )}
        </Space>
      ),
    },
  ];

  return (
    <Space orientation="vertical" style={{ width: "100%" }} size="middle">
      <Flex justify="space-between" align="start">
        <CuttingFilter
          filterForm={filterForm}
          handleFilter={handleFilter}
          handleResetFilter={handleResetFilter}
        />
        {(factory.full || factory.create) && (
          <TooltipButton
            tooltip={t("add_tooltip")}
            type="primary"
            icon={<PlusOutlined />}
            onClick={() => {
              setSelectedRecord(null);
              setOpen(true);
            }}>
            {tc("add")}
          </TooltipButton>
        )}
      </Flex>

      <CustomTable<ICutting>
        rowKey="cutting_machine_id"
        columns={columns}
        dataSource={data?.data?.records || []}
        loading={isLoading}
        tableId="cutting-machine-table"
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

      <CuttingSheet
        open={open}
        onClose={() => setOpen(false)}
        record={selectedRecord}
      />
    </Space>
  );
};
