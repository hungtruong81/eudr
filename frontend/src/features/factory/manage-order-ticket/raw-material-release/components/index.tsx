"use client";
import { PlusOutlined } from "@ant-design/icons";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { Flex, Space, message } from "antd";
import React, { useState } from "react";
import dayjs from "dayjs";
import { TooltipButton } from "@/components/tooltip-button";
import { handleApiError } from "@/lib/api-error";
import {
  deleteRawMaterialRelease,
  getRawMaterialRelease,
  getRawMaterialReleaseById,
  IGerRawMaterialReleaseParams,
} from "../actions";
import { IRawMaterialRelease, IRawMaterialReleaseByCode } from "../types";
import RawMaterialReleaseFilter from "./raw-material-release-filter";
import RawMaterialReleaseCard from "./raw-material-release-card";
import RawMaterialReleaseForm from "./raw-material-release-form";
import { Row, Col, Spin, Empty, Pagination } from "antd";
import { usePermissions } from "@/contexts/permission-context";
import { useTranslations } from "next-intl";

const RawMaterialRelease = () => {
  const t = useTranslations("Factory.material_release");
  const tc = useTranslations("Common");

  const queryClient = useQueryClient();
  const [params, setParams] = useState<Partial<IGerRawMaterialReleaseParams>>({
    page: 1,
    limit: 10,
    created_date_from: dayjs().subtract(1, "month").format("YYYY-MM-DD"),
    created_date_to: dayjs().format("YYYY-MM-DD"),
  });
  const { rawMaterialRelease } = usePermissions();
  const [openForm, setOpenForm] = useState(false);
  const [selectedRecord, setSelectedRecord] =
    useState<IRawMaterialReleaseByCode | null>(null);
  const [fetchingDetail, setFetchingDetail] = useState(false);

  const { data, isFetching, refetch } = useQuery({
    queryKey: ["raw-material-release", params],
    queryFn: () => getRawMaterialRelease(params),
  });

  const deleteMutation = useMutation({
    mutationFn: deleteRawMaterialRelease,
    onSuccess: () => {
      message.success(t("delete_success"));
      queryClient.invalidateQueries({ queryKey: ["raw-material-release"] });
    },
    onError: (error) => {
      handleApiError(error);
    },
  });

  const handleSearch = (newParams: Partial<IGerRawMaterialReleaseParams>) => {
    setParams((prev) => ({
      ...prev,
      ...newParams,
      page: 1,
    }));
  };

  const handleAdd = () => {
    setSelectedRecord(null);
    setOpenForm(true);
  };

  const handleEdit = async (record: IRawMaterialRelease) => {
    try {
      setFetchingDetail(true);
      const res = await getRawMaterialReleaseById(record.material_release_code);
      if (res?.data) {
        setSelectedRecord(res.data);
        setOpenForm(true);
      }
    } catch (error) {
      handleApiError(error);
    } finally {
      setFetchingDetail(false);
    }
  };

  const handleDelete = (record: IRawMaterialRelease) => {
    deleteMutation.mutate(record.material_release_code);
  };

  const handleView = (record: IRawMaterialRelease) => {
    // For now, view is same as edit or could be a read-only view
    handleEdit(record);
  };

  return (
    <Space orientation="vertical" style={{ width: "100%" }} size="large">
      <Flex align="center" justify="space-between">
        <RawMaterialReleaseFilter onSearch={handleSearch} />
        {(rawMaterialRelease.create || rawMaterialRelease.full) && (
          <TooltipButton
            tooltip={t("add_title")}
            icon={<PlusOutlined />}
            type="primary"
            onClick={handleAdd}>
            {tc("add")}
          </TooltipButton>
        )}
      </Flex>

      <Spin spinning={isFetching || fetchingDetail}>
        {(!data?.data?.records || data.data.records.length === 0) &&
        !isFetching ? (
          <Empty description={t("empty_description")} />
        ) : (
          <Flex vertical gap="large">
            <Row gutter={[16, 16]}>
              {data?.data?.records?.map((record) => (
                <Col
                  xs={24}
                  sm={24}
                  md={12}
                  lg={8}
                  xl={6}
                  key={record.material_release_id}>
                  <RawMaterialReleaseCard
                    record={record}
                    onEdit={handleEdit}
                    onDelete={handleDelete}
                    onView={handleView}
                    deleting={
                      deleteMutation.isPending &&
                      deleteMutation.variables === record.material_release_code
                    }
                    permission={rawMaterialRelease}
                  />
                </Col>
              ))}
            </Row>

            {data && data.data.total_records > 0 && (
              <Flex justify="flex-end">
                <Pagination
                  current={+data.data.current_page}
                  total={+data.data.total_records}
                  pageSize={+data.data.page_limit}
                  showSizeChanger
                  onChange={(page, limit) => {
                    setParams((prev) => ({
                      ...prev,
                      page,
                      limit,
                    }));
                  }}
                />
              </Flex>
            )}
          </Flex>
        )}
      </Spin>

      <RawMaterialReleaseForm
        open={openForm}
        onClose={() => setOpenForm(false)}
        record={selectedRecord}
        onSuccess={refetch}
      />
    </Space>
  );
};

export default RawMaterialRelease;
