"use client";
import CustomTable, { CustomColumnTypeTable } from "@/components/custom-table";
import { TooltipButton } from "@/components/tooltip-button";
import { handleApiError } from "@/lib/api-error";
import {
  DeleteOutlined,
  EditOutlined,
  EyeOutlined,
  PlusOutlined,
} from "@ant-design/icons";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Flex, Form, message, Space } from "antd";
import { useEffect, useState } from "react";
import {
  createRawMaterialTank,
  deleteRawMaterialTank,
  getRawMaterialTank,
  IGetRawMaterialTankParams,
  updateRawMaterialTank,
} from "../actions";
import { IRawMaterialTank } from "../types";
import { RawMaterialTankFilter } from "./raw-material-tank-filter";
import RawMaterialTankHistory from "./raw-material-tank-history";
import { RawMaterialTankSheet } from "./raw-material-tank-sheet";
import { ConfirmTooltipButton } from "@/components/confirm-tooltip-button";
import { usePermissions } from "@/contexts/permission-context";
import { useTranslations } from "next-intl";

const RawMaterialTank = () => {
  const t = useTranslations("Factory.metadata.raw_material_tank");
  const tc = useTranslations("Common");

  const [params, setParams] = useState<IGetRawMaterialTankParams>({
    page: 1,
    limit: 10,
  });
  const { rawMaterialTank } = usePermissions();
  const [open, setOpen] = useState(false);
  const [openHistory, setOpenHistory] = useState(false);
  const [record, setRecord] = useState<IRawMaterialTank | null>(null);
  const [form] = Form.useForm();
  const [filterForm] = Form.useForm();
  const queryClient = useQueryClient();

  const { data, isLoading } = useQuery({
    queryKey: ["raw-material-tank", params],
    queryFn: () => getRawMaterialTank(params),
  });

  const deleteMutation = useMutation({
    mutationFn: deleteRawMaterialTank,
    onSuccess: () => {
      message.success(t("delete_success"));
      queryClient.invalidateQueries({ queryKey: ["raw-material-tank"] });
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const handleEdit = (record: IRawMaterialTank) => {
    setRecord(record);
    setOpen(true);
  };

  const handleDelete = (record: IRawMaterialTank) => {
    deleteMutation.mutate(record.raw_material_tank_code);
  };

  useEffect(() => {
    if (open && record) {
      form.setFieldsValue(record);
    }
  }, [open, record, form]);

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

  const columns: CustomColumnTypeTable<IRawMaterialTank>[] = [
    {
      title: t("name"),
      dataIndex: "raw_material_tank_name",
    },
    {
      title: t("factory"),
      dataIndex: "factory_name",
    },
    {
      title: t("tank_type"),
      dataIndex: "tank_type",
      render: (type) => {
        const types: Record<string, string> = {
          latex: t("latex"),
          scrap_rubber: t("scrap_rubber"),
          mixed: t("mixed"),
        };
        return types[type] || type;
      },
    },
    {
      title: t("capacity"),
      dataIndex: "capacity",
      type: "number",
    },
    {
      title: t("location"),
      dataIndex: "location",
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
          {(rawMaterialTank.full || rawMaterialTank.update) && (
            <TooltipButton
              tooltip={t("edit_tooltip")}
              type="primary"
              ghost
              icon={<EditOutlined />}
              onClick={() => handleEdit(record)}
            />
          )}
          {(rawMaterialTank.full || rawMaterialTank.delete) && (
            <ConfirmTooltipButton
              confirmTitle={tc("confirm_delete")}
              confirmDescription={t("confirm_delete_desc", {
                name: record.raw_material_tank_name,
              })}
              tooltip={t("delete_tooltip")}
              danger
              icon={<DeleteOutlined />}
              onConfirm={() => handleDelete(record)}
              loading={
                deleteMutation.isPending &&
                deleteMutation.variables === record.raw_material_tank_code
              }
            />
          )}
          <TooltipButton
            tooltip={t("history_tooltip")}
            type="dashed"
            icon={<EyeOutlined />}
            onClick={() => {
              setRecord(record);
              setOpenHistory(true);
            }}
          />
        </Space>
      ),
    },
  ];

  return (
    <Space orientation="vertical" style={{ width: "100%" }}>
      <Flex justify="space-between">
        <RawMaterialTankFilter
          filterForm={filterForm}
          handleFilter={handleFilter}
          handleResetFilter={handleResetFilter}
        />
        {(rawMaterialTank.full || rawMaterialTank.create) && (
          <TooltipButton
            tooltip={t("add_title")}
            type="primary"
            icon={<PlusOutlined />}
            onClick={() => {
              setRecord(null);
              setOpen(true);
            }}>
            {tc("add")}
          </TooltipButton>
        )}
      </Flex>

      <CustomTable<IRawMaterialTank>
        rowKey="raw_material_tank_id"
        columns={columns}
        dataSource={data?.data?.records || []}
        loading={isLoading}
        tableId="raw-material-tank-table"
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

      <RawMaterialTankSheet
        open={open}
        onClose={() => setOpen(false)}
        record={record}
      />

      <RawMaterialTankHistory
        rawMaterialTankCode={record?.raw_material_tank_code || ""}
        open={openHistory}
        onClose={() => setOpenHistory(false)}
      />
    </Space>
  );
};

export default RawMaterialTank;
