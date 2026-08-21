"use client";
import CustomTable, { CustomColumnTypeTable } from "@/components/custom-table";
import BaseSheet from "@/components/shared/base-sheet";
import { getLands, IGetLandParams } from "@/features/manage-land/land/actions";
import {
  IGetListLandShareByUserParams,
  landShare,
  listLandShareByUser,
  listUserSharedLand,
  revokeShareLand,
} from "@/features/manage-land/land/actions-share";
import { IListLandShareByUser, IPlot } from "@/features/manage-land/land/types";
import { handleApiError } from "@/lib/api-error";
import { DeleteOutlined } from "@ant-design/icons";
import { useQuery } from "@tanstack/react-query";
import {
  Button,
  Card,
  Flex,
  Input,
  message,
  Select,
  Space,
  Typography,
} from "antd";
import React, { useState } from "react";
import { IConnection } from "../types";

const { Text } = Typography;

interface ILandShareModalProps {
  open: boolean;
  onClose: () => void;
  onSuccess: () => void;
  isFarmer: boolean;
  target_user_connect: IConnection | null;
}

import { useTranslations } from "next-intl";

const LandShareModal = ({
  open,
  onClose,
  onSuccess,
  isFarmer,
  target_user_connect,
}: ILandShareModalProps) => {
  const tCommon = useTranslations("Common");
  const tConnection = useTranslations("Connection");

  const [params, setParams] = useState<Partial<IGetListLandShareByUserParams>>({
    page: 1,
    limit: 10,
    search: "",
    status: "all",
  });

  const [isSelectingLand, setIsSelectingLand] = useState(false);
  const [selectedLands, setSelectedLands] = useState<IPlot[]>([]);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const [paramsLand, setParamsLand] = useState<Partial<IGetLandParams>>({
    page: 1,
    limit: 10,
    search: "",
    is_approved: 1,
  });

  const {
    data: listLandShareByUserCode,
    refetch: refetchListLandShareByUsser,
  } = useQuery({
    queryKey: ["land-share", params, target_user_connect?.user_code],
    queryFn: () =>
      listLandShareByUser({
        ...params,
        shared_with_user_code: target_user_connect?.user_code,
      }),
    enabled: !!target_user_connect?.user_code && isFarmer && !isSelectingLand,
  });

  const { data: myLand } = useQuery({
    queryKey: ["my-land", paramsLand],
    queryFn: () => getLands(paramsLand),
    enabled: isFarmer && isSelectingLand,
  });

  const sharedColumns: CustomColumnTypeTable<IListLandShareByUser>[] = [
    {
      title: tConnection("plot_code"),
      dataIndex: "plot_code",
      render: (val) => val.toUpperCase(),
    },
    { title: tConnection("area"), dataIndex: "land_area" },
    { title: tCommon("address"), dataIndex: "address" },
    { title: tConnection("year_of_planting"), dataIndex: "year_of_planting" },
    {
      title: tCommon("actions"),
      dataIndex: "actions",
      fixed: "right",
      render: (_, record) => (
        <Button
          danger
          onClick={async () => {
            try {
              await revokeShareLand({
                plot_code: record.plot_code,
                shared_with_user_code: target_user_connect?.user_code!,
              });
              refetchListLandShareByUsser();
              message.success(tConnection("stop_sharing_success"));
            } catch (error) {
              handleApiError(error);
            }
          }}>
          {tConnection("stop_sharing")}
        </Button>
      ),
    },
  ];

  const myLandColumns: CustomColumnTypeTable<any>[] = [
    {
      title: tConnection("plot_code"),
      dataIndex: "plot_code",
      render: (val) => val.toUpperCase(),
    },
    { title: tConnection("plot_name"), dataIndex: "plantation_name" },
    { title: tConnection("area"), dataIndex: "land_area" },
    { title: tConnection("crop"), dataIndex: "crop_type" },
    { title: tConnection("year_of_planting"), dataIndex: "year_of_planting" },
    { title: tCommon("address"), dataIndex: "address" },
    { title: tCommon("created_at"), dataIndex: "created_at", type: "date" },
  ];

  const rowSelection = {
    selectedRowKeys: selectedLands.map((land) => land.plot_id),
    onChange: (selectedRowKeys: React.Key[], selectedRows: any[]) => {
      setSelectedLands(selectedRows);
    },
    preserveSelectedRowKeys: true,
  };

  const handleRemoveSelectedLand = (plotId: number) => {
    setSelectedLands((prev) => prev.filter((land) => land.plot_id !== plotId));
  };

  const handleConfirmShare = async () => {
    if (selectedLands.length === 0) {
      message.warning(tConnection("select_at_least_one_land"));
      return;
    }

    setIsSubmitting(true);
    try {
      await landShare({
        shared_with_user_code: target_user_connect?.user_code!,
        plot_ids: selectedLands.map((land) => land.plot_id),
      });
      message.success(tConnection("share_land_success"));

      setSelectedLands([]);
      setIsSelectingLand(false);
      refetchListLandShareByUsser();
    } catch (error) {
      handleApiError(error);
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleCloseSheet = () => {
    setIsSelectingLand(false);
    setSelectedLands([]);
    onClose();
  };

  if (!target_user_connect) return null;

  return (
    <BaseSheet
      open={open}
      onClose={handleCloseSheet}
      title={
        isFarmer
          ? `${tConnection("share_land_to_user")}: ${tCommon("phone_number")}: ${target_user_connect.phone} | ${tCommon("full_name").toUpperCase()}: ${target_user_connect.full_name} | ${tConnection("user_group")}: ${target_user_connect.user_roles.map((role) => role.description).join(", ")}`
          : `${tConnection("received_land_from")} ${target_user_connect.full_name}`
      }
      extra={null}>
      <Space orientation="vertical" style={{ width: "100%" }} size="large">
        {!isSelectingLand && (
          <Space orientation="vertical" style={{ width: "100%" }}>
            <Flex gap={4}>
              <Select
                style={{ width: 200 }}
                defaultValue="all"
                options={[
                  { label: tCommon("all"), value: "all" },
                  { label: tConnection("active"), value: "active" },
                  { label: tConnection("revoked"), value: "revoked" },
                ]}
                onChange={(val) =>
                  setParams({
                    ...params,
                    status: val as "all" | "active" | "revoked",
                  })
                }
              />
              <Button type="primary" onClick={() => setIsSelectingLand(true)}>
                {tConnection("select_land_to_share")}
              </Button>
            </Flex>

            <CustomTable
              dataSource={listLandShareByUserCode?.data?.records || []}
              columns={sharedColumns}
              tableId="listLandShareByUserCode"
              rowKey="plot_code"
              pagination={{
                current: params.page,
                pageSize: params.limit,
                total: listLandShareByUserCode?.data?.total_records,
                onChange: (page, pageSize) => {
                  setParams((prev) => ({ ...prev, page, limit: pageSize }));
                },
              }}
              scroll={{ x: "max-content" }}
            />
          </Space>
        )}

        {isSelectingLand && (
          <Space orientation="vertical" style={{ width: "100%" }} size="large">
            <Input.Search
              placeholder={tConnection("search_land")}
              onSearch={(value) =>
                setParamsLand({ ...paramsLand, search: value })
              }
              enterButton
            />

            <CustomTable
              dataSource={myLand?.data?.records || []}
              columns={myLandColumns}
              tableId="listMyLand"
              rowKey="plot_id"
              rowSelection={rowSelection}
              pagination={{
                current: paramsLand.page,
                pageSize: paramsLand.limit,
                total: myLand?.data?.total_records,
                onChange: (page, pageSize) => {
                  setParamsLand((prev) => ({ ...prev, page, limit: pageSize }));
                },
              }}
              scroll={{ x: "max-content" }}
            />

            <Card
              title={
                <b>
                  {tConnection("selected_lands_to_share")}:{" "}
                  <span style={{ color: "red" }}>
                    {tConnection("num_lands", {
                      count: selectedLands.length,
                    })}
                  </span>
                </b>
              }
              style={{ width: "100%" }}>
              {selectedLands.length === 0 ? (
                <Text type="secondary">{tConnection("no_land_selected")}</Text>
              ) : (
                <Flex
                  vertical
                  gap={8}
                  style={{ maxHeight: "300px", overflowY: "auto" }}>
                  {selectedLands.map((land, index) => (
                    <Flex
                      key={land.plot_id}
                      align="center"
                      justify="space-between"
                      style={{
                        padding: "8px 12px",
                        border: "1px solid #f0f0f0",
                        borderRadius: "6px",
                        backgroundColor: "#fafafa",
                      }}>
                      <Space
                        separator={<span style={{ color: "#d9d9d9" }}>|</span>}
                        wrap>
                        <Text>
                          {index + 1}. {tConnection("plot_code")}:{" "}
                          {land.plot_code}
                        </Text>
                        <Text>
                          {tConnection("area")}: {land.land_area} ha
                        </Text>
                        <Text>
                          {tConnection("crop")}: {land.crop_type}
                        </Text>
                        <Text>
                          {tConnection("year_of_planting")}:{" "}
                          {land.year_of_planting}
                        </Text>
                      </Space>

                      <Button
                        type="text"
                        danger
                        icon={<DeleteOutlined />}
                        onClick={() => handleRemoveSelectedLand(land.plot_id)}
                      />
                    </Flex>
                  ))}
                </Flex>
              )}
            </Card>

            <Flex justify="flex-end" gap={8} style={{ marginTop: "16px" }}>
              <Button onClick={() => setIsSelectingLand(false)}>
                {tCommon("cancel")}
              </Button>
              <Button
                type="primary"
                onClick={handleConfirmShare}
                loading={isSubmitting}
                disabled={selectedLands.length === 0}>
                {tCommon("confirm")}
              </Button>
            </Flex>
          </Space>
        )}
      </Space>
    </BaseSheet>
  );
};

export default LandShareModal;
