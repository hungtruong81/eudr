"use client";
import { useQuery } from "@tanstack/react-query";
import React, { useState } from "react";
import { deletePlan, getPlan, IGetPlanParams } from "../actions";
import CustomTable, { CustomColumnTypeTable } from "@/components/custom-table";
import { IHarvestPlan, IHarvestPlanLand } from "../types";
import { Flex, Modal, Space, Tag } from "antd";
import { usePermissions } from "@/contexts/permission-context";
import { TooltipButton } from "@/components/tooltip-button";
import {
  DeleteOutlined,
  EditOutlined,
  EyeOutlined,
  PlusOutlined,
} from "@ant-design/icons";
import PlanFilter from "./plan-filter";
import dayjs from "dayjs";
import PlanSheet from "./plan-sheet";
import { useRouter } from "nextjs-toploader/app";
import { ConfirmTooltipButton } from "@/components/confirm-tooltip-button";
import { useTranslations } from "next-intl";

const Plan = () => {
  const t = useTranslations("Harvest.Plan");
  const router = useRouter();
  const { harvest } = usePermissions();
  const [params, setParams] = useState<Partial<IGetPlanParams>>({
    page: 1,
    limit: 10,
  });

  const [isOpenPlanSheet, setIsOpenPlanSheet] = useState(false);
  const [selectedPlan, setSelectedPlan] = useState<IHarvestPlan | null>(null);

  const { data, refetch } = useQuery({
    queryKey: ["harvest-plan", params],
    queryFn: () => getPlan(params),
  });

  const handleDelete = async (harvest_plan_code: string) => {
    await deletePlan(harvest_plan_code);
    refetch();
  };

  const columns: CustomColumnTypeTable<IHarvestPlan>[] = [
    {
      title: t("harvest_plan_code"),
      key: "harvest_plan_code",
      render: (value) => value?.harvest_plan_code?.toUpperCase(),
    },
    { title: t("farmer_name"), dataIndex: "farmer_name" },
    {
      title: t("num_plots"),
      dataIndex: "lands",
      render: (value) => value.length,
      autoFilter: false,
    },
    {
      title: t("harvest_time"),
      render: (record) =>
        `${dayjs(record.harvest_start_date).format("DD/MM/YYYY")} - ${dayjs(
          record.harvest_end_date,
        ).format("DD/MM/YYYY")}`,
    },
    { title: t("expected_yield"), dataIndex: "expected_yield" },
    {
      title: t("eudr_status"),
      dataIndex: "eudr_status",
      render: (value) => (
        <Tag
          color={
            value === 0 ? "processing" : value === 1 ? "success" : "error"
          }>
          {value === 0 ? t("pending") : value === 1 ? "Eudr" : "Non Eudr"}
        </Tag>
      ),
    },
    { title: t("created_at"), dataIndex: "created_at", type: "date" },
    {
      title: t("actions"),
      dataIndex: "actions",
      render: (_, record) => (
        <Space>
          {(harvest.plan.update || harvest.plan.full) && (
            <TooltipButton
              tooltip={t("edit")}
              icon={<EditOutlined />}
              type="primary"
              ghost
              onClick={() => {
                setIsOpenPlanSheet(true);
                setSelectedPlan(record);
              }}
            />
          )}

          <TooltipButton
            tooltip={t("view_detail")}
            icon={<EyeOutlined />}
            type="dashed"
            onClick={() => {
              router.push(`/farmer/harvest-plan/${record.harvest_plan_code}`);
            }}
          />

          {(harvest.plan.delete || harvest.plan.full) && (
            <ConfirmTooltipButton
              tooltip={t("delete")}
              confirmTitle={t("confirm_delete")}
              confirmDescription={t("delete_confirm_msg")}
              icon={<DeleteOutlined />}
              danger
              onConfirm={() => handleDelete(record.harvest_plan_code)}
            />
          )}
        </Space>
      ),
    },
  ];

  const handleSearch = (params: Partial<IGetPlanParams>) => {
    setParams((prev) => ({
      ...prev,
      ...params,
      harvest_start_date: params.harvest_start_date
        ? dayjs(params.harvest_start_date).format("YYYY-MM-DD")
        : undefined,
      harvest_end_date: params.harvest_end_date
        ? dayjs(params.harvest_end_date).format("YYYY-MM-DD")
        : undefined,
      page: 1,
      limit: 10,
    }));
  };

  return (
    <Space orientation="vertical" style={{ width: "100%" }}>
      <Flex align="center" justify="space-between">
        <PlanFilter onSearch={handleSearch} />

        {(harvest.plan.create || harvest.plan.full) && (
          <TooltipButton
            tooltip={t("add_plan")}
            icon={<PlusOutlined />}
            type="primary"
            onClick={() => {
              setIsOpenPlanSheet(true);
              setSelectedPlan(null);
            }}>
            {t("add_new")}
          </TooltipButton>
        )}
      </Flex>
      <CustomTable<IHarvestPlan>
        rowKey="harvest_plan_id"
        columns={columns}
        dataSource={data?.data?.records || []}
        tableId="harvest-plan-table"
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

      <PlanSheet
        open={isOpenPlanSheet}
        onClose={() => setIsOpenPlanSheet(false)}
        plan={selectedPlan}
        onRefresh={refetch}
      />
    </Space>
  );
};

export default Plan;
