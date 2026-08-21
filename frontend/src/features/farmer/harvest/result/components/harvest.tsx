"use client";
import { useQuery } from "@tanstack/react-query";
import React, { useState } from "react";
import { getSchedules, IGetSchedulesParams } from "../../plan/actions";
import CustomTable, { CustomColumnTypeTable } from "@/components/custom-table";
import { ISchedule } from "../../plan/types";
import { Flex, Space, Tag } from "antd";
import { usePermissions } from "@/contexts/permission-context";
import { TooltipButton } from "@/components/tooltip-button";
import { EditOutlined, EyeOutlined } from "@ant-design/icons";
import HarvestFilter from "./harvest-filter";
import dayjs from "dayjs";
import { useRouter } from "nextjs-toploader/app";
import { useTranslations } from "next-intl";
import HarvestSheet from "./harvest-sheet";

const Harvest = () => {
  const t = useTranslations("Harvest.Result");
  const router = useRouter();
  const { harvest } = usePermissions();
  const [params, setParams] = useState<IGetSchedulesParams>({
    page: 1,
    limit: 10,
  });

  const [isOpenSheet, setIsOpenSheet] = useState(false);
  const [selectedSchedule, setSelectedSchedule] = useState<ISchedule | null>(
    null,
  );

  const { data, refetch } = useQuery({
    queryKey: ["harvest-schedule", params],
    queryFn: () => getSchedules(params),
  });

  const columns: CustomColumnTypeTable<ISchedule>[] = [
    {
      title: t("schedule_code"),
      key: "harvest_schedule_code",
      render: (value) => value?.harvest_schedule_code?.toUpperCase(),
    },
    { title: t("plot"), dataIndex: "plot_name" },
    {
      title: t("harvest_date"),
      dataIndex: "pickup_date",
      render: (value) => dayjs(value).format("DD/MM/YYYY"),
    },
    { title: t("harvest_time"), dataIndex: "pickup_time" },
    {
      title: t("expected_yield"),
      dataIndex: "expected_yield",
      render: (value) => Number(value).toLocaleString("vi-VN") + " kg",
    },
    {
      title: t("actual_yield"),
      dataIndex: "actual_yield",
      render: (value) => (
        <span className="font-semibold text-green-600">
          {Number(value).toLocaleString("vi-VN")} kg
        </span>
      ),
    },
    { title: "Chế độ thu hoạch", dataIndex: "tapping_regime" },
    {
      title: t("actions"),
      dataIndex: "actions",
      fixed: "right",
      render: (_, record) => (
        <Space>
          {(harvest.result.update.all ||
            harvest.result.update.own ||
            harvest.result.update.self ||
            harvest.result.full) && (
            <TooltipButton
              tooltip={t("update_yield")}
              icon={<EditOutlined />}
              type="primary"
              ghost
              onClick={() => {
                setSelectedSchedule(record);
                setIsOpenSheet(true);
              }}
            />
          )}

          <TooltipButton
            tooltip={t("view_detail")}
            icon={<EyeOutlined />}
            type="dashed"
            onClick={() => {
              router.push(`/farmer/harvest/${record.harvest_schedule_code}`);
            }}
          />
        </Space>
      ),
    },
  ];

  const handleSearch = (searchParams: Partial<IGetSchedulesParams>) => {
    setParams((prev) => ({
      ...prev,
      ...searchParams,
      page: 1,
      limit: 10,
    }));
  };

  return (
    <Space orientation="vertical" className="w-full">
      <Flex align="center" justify="space-between">
        <HarvestFilter onSearch={handleSearch} />
      </Flex>
      <CustomTable<ISchedule>
        rowKey="harvest_schedule_id"
        columns={columns}
        dataSource={data?.data?.records || []}
        tableId="harvest-schedule-table"
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

      <HarvestSheet
        open={isOpenSheet}
        onClose={() => setIsOpenSheet(false)}
        schedule={selectedSchedule}
        onRefresh={refetch}
      />
    </Space>
  );
};

export default Harvest;
