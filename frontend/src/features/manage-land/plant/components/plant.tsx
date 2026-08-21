"use client";
import React from "react";
import { useMutation, useQuery } from "@tanstack/react-query";
import { deletePlant, getPlants, IGetPlantParams } from "../actions";
import CustomTable, { CustomColumnTypeTable } from "@/components/custom-table";
import { IPlant } from "../types";
import { usePermissions } from "@/contexts/permission-context";
import { Flex, message, Space } from "antd";
import {
  DeleteOutlined,
  EditOutlined,
  EyeOutlined,
  PlusOutlined,
} from "@ant-design/icons";
import { TooltipButton } from "@/components/tooltip-button";
import PlantFilter from "./plant-filter";
import PlantSheet from "./plant-sheet";
import { useTranslations } from "next-intl";
import { useRouter } from "nextjs-toploader/app";
import { handleApiError } from "@/lib/api-error";
import { ConfirmTooltipButton } from "@/components/confirm-tooltip-button";

const Plant = () => {
  const t = useTranslations("ManageLand.Plant");
  const tCommon = useTranslations("Common");
  const router = useRouter();
  const { plant } = usePermissions();
  const [params, setParams] = React.useState<Partial<IGetPlantParams>>({
    page: 1,
    limit: 10,
    search: "",
    plot_code: "",
  });

  const [isOpenPlantSheet, setIsOpenPlantSheet] = React.useState(false);
  const [selectedPlant, setSelectedPlant] = React.useState<IPlant | null>(null);

  const { data, refetch } = useQuery({
    queryKey: ["plants", params],
    queryFn: () => getPlants(params),
  });

  const deleteMutation = useMutation({
    mutationFn: (plant_code: string) => deletePlant(plant_code),
    onSuccess: () => {
      message.success(t("delete_success"));
      refetch();
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const columns: CustomColumnTypeTable<IPlant>[] = [
    {
      title: t("plant_code"),
      dataIndex: "plant_code",
      render: (text) => text.toUpperCase(),
    },
    { title: t("plot_name"), dataIndex: "plot_name" },
    { title: t("crop_type"), dataIndex: "crop_type" },
    { title: t("year_of_planting"), dataIndex: "year_of_planting" },
    { title: t("expected_harvest"), dataIndex: "expected_harvest" },
    { title: tCommon("created_at"), dataIndex: "created_at", type: "date" },
    {
      title: tCommon("actions"),
      dataIndex: "actions",
      render: (_, record) => (
        <Space>
          {plant.full ||
            (plant.update && (
              <TooltipButton
                tooltip={tCommon("edit")}
                icon={<EditOutlined />}
                type="primary"
                ghost
                onClick={() => {
                  setIsOpenPlantSheet(true);
                  setSelectedPlant(record);
                }}
              />
            ))}
          <TooltipButton
            tooltip={tCommon("view_detail")}
            icon={<EyeOutlined />}
            type="dashed"
            onClick={() => {
              router.push(`/land/plants/${record.plant_code}`);
            }}
          />
          {(plant.delete || plant.full) && (
            <ConfirmTooltipButton
              confirmTitle={tCommon("confirm_delete")}
              confirmDescription={t("confirm_delete_msg", {
                code: record.plant_code,
              })}
              tooltip={tCommon("delete")}
              icon={<DeleteOutlined />}
              danger
              onConfirm={() => {
                deleteMutation.mutate(record.plant_code);
              }}
            />
          )}
        </Space>
      ),
    },
  ];

  const handleSearch = (params: Partial<IGetPlantParams>) => {
    setParams((prev) => ({
      ...prev,
      ...params,
      page: 1,
    }));
  };

  return (
    <Space orientation="vertical" style={{ width: "100%" }}>
      <Flex align="center" justify="space-between">
        <PlantFilter onSearch={handleSearch} />

        {(plant.create || plant.full) && (
          <TooltipButton
            tooltip={t("add_plant")}
            icon={<PlusOutlined />}
            type="primary"
            onClick={() => {
              setIsOpenPlantSheet(true);
              setSelectedPlant(null);
            }}>
            {tCommon("add")}
          </TooltipButton>
        )}
      </Flex>
      <CustomTable
        rowKey="plant_code"
        columns={columns}
        dataSource={data?.data?.records || []}
        tableId="plant-list-table"
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
      />

      <PlantSheet
        open={isOpenPlantSheet}
        onClose={() => setIsOpenPlantSheet(false)}
        record={selectedPlant}
        onRefresh={refetch}
      />
    </Space>
  );
};

export default Plant;
