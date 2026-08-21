"use client";
import CustomTable, { CustomColumnTypeTable } from "@/components/custom-table";
import { TooltipButton } from "@/components/tooltip-button";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Flex, Form, Modal, Space, Tag, Typography, message } from "antd";
import React, { useState } from "react";
import dayjs from "dayjs";
import {
  CheckCircleOutlined,
  CloseCircleOutlined,
  EditOutlined,
  EyeOutlined,
  PlusOutlined,
} from "@ant-design/icons";
import {
  cancelExternalMaterial,
  confirmExternalMaterial,
  getExternalMaterial,
  IGetExternalMaterialParams,
} from "../actions";
import { IExternalMaterial, IExternalMaterialLand } from "../types";
import ExternalMaterialFilter from "./external-material-filter";
import ExternalMaterialForm from "./external-material-form";
import ExternalMaterialDetail from "./external-material-detail";
import { useTranslations } from "next-intl";

const { Text } = Typography;

const ExternalMaterial = () => {
  const t = useTranslations("Factory.external_material");
  const ts = useTranslations("Status");
  const tc = useTranslations("Common");

  const mapStatus: Record<string, string> = {
    pending: ts("pending"),
    draft: ts("draft"),
    confirmed: ts("confirmed"),
    cancelled: ts("cancelled"),
  };

  const mapColor: Record<string, string> = {
    pending: "warning",
    draft: "default",
    confirmed: "success",
    cancelled: "error",
  };

  const [params, setParams] = useState<IGetExternalMaterialParams>({
    page: 1,
    limit: 10,
    status: "all",
  });

  const [filterForm] = Form.useForm();
  const [openForm, setOpenForm] = useState(false);
  const [openDetail, setOpenDetail] = useState(false);
  const [selectedCode, setSelectedCode] = useState<string | null>(null);
  const [selectedRecord, setSelectedRecord] =
    useState<IExternalMaterial | null>(null);

  const queryClient = useQueryClient();

  const { data, isLoading } = useQuery({
    queryKey: ["external-material", params],
    queryFn: () => getExternalMaterial(params),
  });

  const mutateConfirm = useMutation({
    mutationFn: (external_code: string) =>
      confirmExternalMaterial(external_code),
    onSuccess: () => {
      message.success(t("confirm_success"));
      queryClient.invalidateQueries({ queryKey: ["external-material"] });
    },
    onError: () => message.error(t("confirm_fail")),
  });

  const mutateCancel = useMutation({
    mutationFn: (external_code: string) =>
      cancelExternalMaterial(external_code),
    onSuccess: () => {
      message.success(t("cancel_success"));
      queryClient.invalidateQueries({ queryKey: ["external-material"] });
    },
    onError: () => message.error(t("cancel_fail")),
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
      status: "all",
    });
  };

  const handleConfirm = (code: string) => {
    Modal.confirm({
      title: t("confirm_modal_title"),
      content: t("confirm_modal_content", { code }),
      okText: tc("confirm"),
      cancelText: tc("back"),
      onOk: () => mutateConfirm.mutate(code),
    });
  };

  const handleCancel = (code: string) => {
    Modal.confirm({
      title: t("cancel_modal_title"),
      content: t("cancel_modal_content", { code }),
      okText: t("cancel_tooltip"),
      okType: "danger",
      cancelText: tc("back"),
      onOk: () => mutateCancel.mutate(code),
    });
  };

  const columns: CustomColumnTypeTable<IExternalMaterial>[] = [
    {
      title: t("code"),
      dataIndex: "external_material_code",
      render: (val) => <Text strong>{val?.toUpperCase()}</Text>,
    },
    {
      title: t("supplier"),
      dataIndex: "supplier_name",
    },
    {
      title: t("lands"),
      dataIndex: "lands",
      render: (lands) => (
        <Space orientation="vertical">
          {lands?.map((land: IExternalMaterialLand) => (
            <Text key={land.external_material_land_id}>
              {land.plot_name} (
              {Number(land.harvest_weight).toLocaleString("vi-VN")})
            </Text>
          ))}
        </Space>
      ),
    },
    {
      title: t("total_amount"),
      dataIndex: "total_amount",
      align: "right",
      render: (val) => Number(val || 0).toLocaleString("vi-VN"),
    },
    {
      title: t("purchase_date"),
      dataIndex: "purchase_date",
      render: (val) => dayjs(val).format("DD/MM/YYYY"),
    },
    {
      title: t("vehicle_plate"),
      render: (_, record) => record.transport?.vehicle_license_plate || "-",
    },
    {
      title: t("status"),
      dataIndex: "status",
      align: "center",
      render: (val) => (
        <Tag color={mapColor[val] || "default"}>{mapStatus[val] || val}</Tag>
      ),
    },
    {
      title: t("actions"),
      align: "center",
      render: (_, record) => (
        <Space>
          <TooltipButton
            tooltip={t("update_tooltip")}
            type="primary"
            ghost
            icon={<EditOutlined />}
            onClick={() => {
              setSelectedRecord(record);
              setOpenForm(true);
            }}
          />
          {record.status !== "confirmed" && record.status !== "cancelled" && (
            <>
              <TooltipButton
                tooltip={t("confirm_tooltip")}
                type="primary"
                icon={<CheckCircleOutlined />}
                style={{ color: "#52c41a", borderColor: "#52c41a" }}
                onClick={() => handleConfirm(record.external_material_code)}
              />
              <TooltipButton
                tooltip={t("cancel_tooltip")}
                danger
                icon={<CloseCircleOutlined />}
                onClick={() => handleCancel(record.external_material_code)}
              />
              <TooltipButton
                tooltip={t("view_tooltip")}
                type="dashed"
                icon={<EyeOutlined />}
                onClick={() => {
                  setSelectedCode(record.external_material_code);
                  setOpenDetail(true);
                }}
              />
            </>
          )}
        </Space>
      ),
    },
  ];

  return (
    <Space orientation="vertical" style={{ width: "100%" }}>
      <Flex justify="space-between">
        <ExternalMaterialFilter
          filterForm={filterForm}
          handleFilter={handleFilter}
          handleResetFilter={handleResetFilter}
        />

        <TooltipButton
          tooltip={t("add_tooltip")}
          type="primary"
          icon={<PlusOutlined />}
          onClick={() => {
            setSelectedRecord(null);
            setOpenForm(true);
          }}>
          {tc("add")}
        </TooltipButton>
      </Flex>

      <CustomTable<IExternalMaterial>
        rowKey="external_material_code"
        columns={columns}
        dataSource={Array.isArray(data?.data?.items) ? data?.data?.items : []}
        loading={isLoading}
        tableId="external-material-table"
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

      <ExternalMaterialDetail
        open={openDetail}
        onClose={() => {
          setOpenDetail(false);
          setSelectedCode(null);
        }}
        externalMaterialCode={selectedCode}
      />

      <ExternalMaterialForm
        open={openForm}
        onClose={() => setOpenForm(false)}
        record={selectedRecord}
      />
    </Space>
  );
};

export default ExternalMaterial;
