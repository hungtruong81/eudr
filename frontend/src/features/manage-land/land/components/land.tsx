"use client";

import CustomTable, { CustomColumnTypeTable } from "@/components/custom-table";
import { TooltipButton } from "@/components/tooltip-button";
import {
  deleteLand,
  getLands,
  IGetLandParams,
} from "@/features/manage-land/land/actions";
import { IPlot } from "@/features/manage-land/land/types";
import {
  CheckOutlined,
  DeleteOutlined,
  EditOutlined,
  EyeOutlined,
  PlusOutlined,
} from "@ant-design/icons";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Flex, message, Space, Tag } from "antd";
import { useState } from "react";
import LandSheet from "./land-sheet";
import LandFilter from "./land-filter";
import ApproveLandModal from "./approve-land";
import { usePermissions } from "@/contexts/permission-context";
import { useRouter } from "next/navigation";
import { ConfirmTooltipButton } from "@/components/confirm-tooltip-button";
import { handleApiError } from "@/lib/api-error";
import { useTranslations } from "next-intl";

const Land = () => {
  const t = useTranslations("ManageLand.Land");
  const tCommon = useTranslations("Common");
  const router = useRouter();
  const { land } = usePermissions();
  const [filter, setFilter] = useState<IGetLandParams>({
    page: 1,
    limit: 10,
  });
  const queryClient = useQueryClient();

  const [isOpenLandSheet, setIsOpenLandSheet] = useState(false);
  const [selectedLand, setSelectedLand] = useState<IPlot | null>(null);

  const [isOpenApproveModal, setIsOpenApproveModal] = useState(false);
  const [selectedApproveLand, setSelectedApproveLand] = useState<IPlot | null>(
    null,
  );

  const { data, isLoading } = useQuery({
    queryKey: ["land", filter],
    queryFn: () => getLands(filter),
  });

  const deleteMutation = useMutation({
    mutationFn: (plot_code: string) => deleteLand(plot_code),
    onSuccess: () => {
      message.success(t("delete_success"));
      queryClient.invalidateQueries({ queryKey: ["land"] });
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const columns: CustomColumnTypeTable<IPlot>[] = [
    {
      title: t("plot_code"),
      dataIndex: "plot_code",
      render: (text) => text.toUpperCase(),
    },
    {
      title: t("farmer_name"),
      dataIndex: "farmer_name",
    },
    {
      title: t("province"),
      dataIndex: "province_name",
    },
    {
      title: t("area_ha"),
      dataIndex: "land_area",
    },
    {
      title: tCommon("status"),
      dataIndex: "status",
    },
    {
      title: t("is_approved"),
      dataIndex: "is_approved",
      render: (text) => (
        <Tag
          color={text === 0 ? "processing" : text === 1 ? "success" : "error"}>
          {text === 0
            ? t("wait_approve")
            : text === 1
              ? t("approved")
              : t("rejected")}
        </Tag>
      ),
    },
    {
      title: tCommon("created_at"),
      dataIndex: "created_at",
      type: "date",
    },
    {
      title: tCommon("actions"),
      dataIndex: "actions",
      render: (_, record) => (
        <Space>
          {land.full && (
            <TooltipButton
              tooltip={t("approve_land")}
              icon={<CheckOutlined />}
              type="default"
              onClick={() => {
                setIsOpenApproveModal(true);
                setSelectedApproveLand(record);
              }}
            />
          )}
          {(land.create || land.full) && (
            <TooltipButton
              tooltip={tCommon("edit")}
              icon={<EditOutlined />}
              type="primary"
              ghost
              onClick={() => {
                setIsOpenLandSheet(true);
                setSelectedLand(record);
              }}
            />
          )}
          <TooltipButton
            tooltip={tCommon("view_detail")}
            icon={<EyeOutlined />}
            type="dashed"
            onClick={() => {
              router.push(`/land/${record.plot_code}`);
            }}
          />

          {(land.delete || land.full) && (
            <ConfirmTooltipButton
              confirmTitle={tCommon("confirm_delete")}
              confirmDescription={t("confirm_delete_msg", {
                code: record.plot_code,
              })}
              tooltip={tCommon("delete")}
              danger
              icon={<DeleteOutlined />}
              onConfirm={() => deleteMutation.mutate(record.plot_code)}
            />
          )}
        </Space>
      ),
    },
  ];

  const handleSearch = (value: Partial<IGetLandParams>) => {
    setFilter({
      ...filter,
      search: value.search,
    });
  };

  return (
    <Space orientation="vertical" style={{ width: "100%" }}>
      <Flex align="center" justify="space-between">
        <LandFilter onSearch={handleSearch} loading={isLoading} />
        {(land.create || land.full) && (
          <TooltipButton
            tooltip={t("add_land")}
            icon={<PlusOutlined />}
            type="primary"
            onClick={() => {
              setIsOpenLandSheet(true);
              setSelectedLand(null);
            }}>
            {tCommon("add")}
          </TooltipButton>
        )}
      </Flex>
      <CustomTable<IPlot>
        rowKey="plot_id"
        columns={columns}
        dataSource={data?.data?.records || []}
        loading={isLoading}
        tableId="land-list-table"
        pagination={{
          current: data?.data?.current_page || 1,
          pageSize: data?.data?.page_limit || 10,
          total: data?.data?.total_records || 0,
        }}
        onChange={(pagination) => {
          setFilter({
            ...filter,
            page: pagination.current || 1,
            limit: pagination.pageSize || 10,
          });
        }}
        scroll={{
          x: "max-content",
        }}
      />

      <LandSheet
        onClose={() => setIsOpenLandSheet(false)}
        open={isOpenLandSheet}
        record={selectedLand}
      />

      <ApproveLandModal
        open={isOpenApproveModal}
        onCancel={() => setIsOpenApproveModal(false)}
        record={selectedApproveLand}
      />
    </Space>
  );
};

export default Land;
