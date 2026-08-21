"use client";
import CustomTable, { CustomColumnTypeTable } from "@/components/custom-table";
import {
  IGetMyLandShare,
  landShareAll,
  myLandShare,
  listUserSharedLand,
} from "@/features/manage-land/land/actions-share";
import {
  ICoordinate,
  IListLandShareByUser,
  IPlot,
} from "@/features/manage-land/land/types";
import { useUser } from "@/providers/user-context";
import { CommonPaginationParams } from "@/types/api";
import { GoogleMap, InfoWindow, Polygon } from "@react-google-maps/api";
import { useQuery } from "@tanstack/react-query";
import { Col, Form, Modal, Row, Select, Space, Table, Typography } from "antd";
import { useMemo, useState } from "react";

const { Text } = Typography;

const containerStyle = {
  width: "100%",
  height: "100%",
  minHeight: "400px",
  borderRadius: "8px",
};

const PlotMap = ({ plot }: { plot: IPlot }) => {
  const tConnection = useTranslations("Connection");

  const detailColumns = [
    {
      title: tConnection("land_info"),
      dataIndex: "label",
      key: "label",
      width: "40%",
      render: (text: string) => <Text type="secondary">{text}</Text>,
    },
    {
      title: tConnection("land_value"),
      dataIndex: "value",
      key: "value",
      render: (text: any) => <Text strong>{text ?? "—"}</Text>,
    },
  ];

  const [infoPosition, setInfoPosition] = useState<{
    lat: number;
    lng: number;
  } | null>(null);
  const { coordinates } = plot;

  const center = useMemo(() => {
    if (!coordinates || coordinates.length === 0) {
      return { lat: 10.762622, lng: 106.660172 };
    }
    const latSum = coordinates.reduce((sum, coord) => sum + coord.lat, 0);
    const lngSum = coordinates.reduce((sum, coord) => sum + coord.lng, 0);
    return {
      lat: latSum / coordinates.length,
      lng: lngSum / coordinates.length,
    };
  }, [coordinates]);

  return (
    <GoogleMap
      mapContainerStyle={containerStyle}
      center={center}
      zoom={17}
      options={{
        mapTypeId: "satellite",
        streetViewControl: false,
        mapTypeControl: true,
      }}>
      {coordinates && coordinates.length > 0 && (
        <Polygon
          paths={coordinates}
          options={{
            fillColor: "#52c41a",
            fillOpacity: 0.35,
            strokeColor: "#389e0d",
            strokeOpacity: 1,
            strokeWeight: 2,
          }}
          onClick={(e) => {
            if (e.latLng) {
              setInfoPosition({ lat: e.latLng.lat(), lng: e.latLng.lng() });
            }
          }}
        />
      )}
      {infoPosition && (
        <InfoWindow
          position={infoPosition}
          onCloseClick={() => setInfoPosition(null)}>
          <div style={{ minWidth: 220 }}>
            <Table
              dataSource={[
                {
                  label: tConnection("plot_name"),
                  value: plot.plot_name?.toUpperCase(),
                },
                {
                  label: tConnection("plot_code"),
                  value: plot.plot_code?.toUpperCase(),
                },
                { label: tConnection("area"), value: `${plot.land_area} ha` },
                { label: tConnection("farmer"), value: plot.farmer_name },
                {
                  label: tConnection("crop"),
                  value: plot.crop_type || plot.plant_type,
                },
              ]}
              columns={detailColumns}
              pagination={false}
              size="small"
              bordered
              rowKey="label"
              showHeader={false}
            />
          </div>
        </InfoWindow>
      )}
    </GoogleMap>
  );
};

interface ISharedUser {
  user_id: number;
  user_code: string;
  full_name: string;
  phone: string;
  email: string;
  register_type: string;
}

import { useTranslations } from "next-intl";
import AppModal from "@/components/modal";

const StaticsShare = () => {
  const tCommon = useTranslations("Common");
  const tConnection = useTranslations("Connection");
  const { isFarmer } = useUser();

  const detailColumns = [
    {
      title: tCommon("info"),
      dataIndex: "label",
      key: "label",
      width: "40%",
      render: (text: string) => <Text type="secondary">{text}</Text>,
    },
    {
      title: tCommon("detail"),
      dataIndex: "value",
      key: "value",
      render: (text: any) => <Text strong>{text ?? "—"}</Text>,
    },
  ];

  const [selectedPlot, setSelectedPlot] = useState<IPlot | null>(null);
  const [params, setParams] = useState<Partial<IGetMyLandShare>>({
    status: "all",
    page: 1,
    limit: 10,
  });

  const [paramsShareAll, setParamsShareAll] = useState<CommonPaginationParams>({
    page: 1,
    limit: 10,
  });

  const [selectedSharedPlotCode, setSelectedSharedPlotCode] = useState<
    string | null
  >(null);
  const [sharedUsersParams, setSharedUsersParams] =
    useState<CommonPaginationParams>({
      page: 1,
      limit: 10,
    });

  const { data: myLandShares } = useQuery({
    queryKey: ["my-land-share", params],
    queryFn: () => myLandShare(params),
    enabled: !isFarmer,
  });

  const columns: CustomColumnTypeTable<IPlot>[] = [
    {
      title: tConnection("plot_code"),
      dataIndex: "plot_code",
      render: (text) => text?.toUpperCase(),
    },
    { title: tConnection("area"), dataIndex: "land_area" },
    { title: tConnection("crop"), dataIndex: "crop_type" },
    { title: tConnection("year_of_planting"), dataIndex: "year_of_planting" },
    { title: tCommon("address"), dataIndex: "province_name" },
    { title: tCommon("status"), dataIndex: "status" },
  ];

  const { data: landsShareAll } = useQuery({
    queryKey: ["land-share-all", paramsShareAll],
    queryFn: () => landShareAll(paramsShareAll),
    enabled: isFarmer,
  });

  const { data: sharedUsersData, isFetching: isFetchingSharedUsers } = useQuery(
    {
      queryKey: [
        "list-user-shared-land",
        selectedSharedPlotCode,
        sharedUsersParams,
      ],
      queryFn: () =>
        listUserSharedLand(selectedSharedPlotCode!, sharedUsersParams),
      enabled: !!selectedSharedPlotCode && isFarmer,
    },
  );

  const columnsLandShareAll: CustomColumnTypeTable<IListLandShareByUser>[] = [
    { title: tConnection("total_shared_users"), dataIndex: "total_shared" },
    {
      title: tConnection("plot_code"),
      dataIndex: "plot_code",
      render: (text) => text?.toUpperCase(),
    },
    { title: tConnection("area"), dataIndex: "land_area" },
    { title: tConnection("crop"), dataIndex: "crop_type" },
    { title: tConnection("year_of_planting"), dataIndex: "year_of_planting" },
    { title: tCommon("address"), dataIndex: "address" },
  ];

  const sharedUserColumns: CustomColumnTypeTable<ISharedUser>[] = [
    { title: tConnection("user_code"), dataIndex: "user_code" },
    {
      title: tCommon("full_name"),
      dataIndex: "full_name",
      render: (text) => <b>{text}</b>,
    },
    { title: tCommon("phone_number"), dataIndex: "phone" },
    {
      title: tCommon("email"),
      dataIndex: "email",
      render: (text) => text || tConnection("not_updated"),
    },
    { title: tConnection("account_type"), dataIndex: "register_type" },
  ];

  return (
    <Space orientation="vertical" style={{ width: "100%" }}>
      {!isFarmer ? (
        <>
          <Form>
            <Form.Item label={tCommon("status")}>
              <Select
                defaultValue="all"
                style={{ width: 120 }}
                onChange={(value) =>
                  setParams((prev) => ({
                    ...prev,
                    status: value as "all" | "active" | "revoked",
                  }))
                }
                options={[
                  { label: tCommon("all"), value: "all" },
                  { label: tConnection("active"), value: "active" },
                  { label: tConnection("revoked"), value: "revoked" },
                ]}
                showSearch
                placeholder={tCommon("search")}
                allowClear
              />
            </Form.Item>
          </Form>
          <CustomTable
            tableId="my-land-share"
            dataSource={myLandShares?.data.records}
            columns={columns}
            rowKey="plot_id"
            pagination={{
              current: params.page,
              pageSize: params.limit,
              total: myLandShares?.data.total_records,
            }}
            onChange={(pagination) => {
              setParams((prev) => ({
                ...prev,
                page: pagination.current,
                limit: pagination.pageSize,
              }));
            }}
            onRow={(record) => ({
              onClick: () => setSelectedPlot(record),
              style: { cursor: "pointer" },
            })}
          />

          <AppModal
            title={`${tConnection("land_detail")}: ${selectedPlot?.plot_code?.toUpperCase()}`}
            open={!!selectedPlot}
            onCancel={() => setSelectedPlot(null)}
            footer={null}
            width={1024}
            centered>
            {selectedPlot && (
              <Row gutter={[24, 24]} style={{ marginTop: 16 }}>
                <Col xs={24} md={10} lg={8}>
                  <Table
                    dataSource={[
                      {
                        label: tConnection("plot_code"),
                        value: selectedPlot.plot_code?.toUpperCase(),
                      },
                      {
                        label: tConnection("plot_name"),
                        value:
                          selectedPlot.plot_name || tConnection("not_updated"),
                      },
                      {
                        label: tConnection("area"),
                        value: `${selectedPlot.land_area} ha`,
                      },
                      {
                        label: tCommon("address"),
                        value:
                          selectedPlot.address ||
                          selectedPlot.plantation_name ||
                          selectedPlot.province_name,
                      },
                      {
                        label: tConnection("crop"),
                        value:
                          selectedPlot.crop_type || selectedPlot.plant_type,
                      },
                      {
                        label: tConnection("year_of_planting"),
                        value: selectedPlot.year_of_planting,
                      },
                      {
                        label: tConnection("coordinates"),
                        value: (
                          <div
                            style={{ maxHeight: "150px", overflowY: "auto" }}>
                            {selectedPlot.coordinates?.length > 0 ? (
                              selectedPlot.coordinates.map((coord, index) => (
                                <div key={index}>
                                  <Text
                                    type="secondary"
                                    style={{ fontSize: "13px" }}>
                                    P{index + 1}: {coord.lat.toFixed(5)},{" "}
                                    {coord.lng.toFixed(5)}
                                  </Text>
                                </div>
                              ))
                            ) : (
                              <Text type="secondary">
                                {tConnection("no_coordinates")}
                              </Text>
                            )}
                          </div>
                        ),
                      },
                    ]}
                    columns={detailColumns}
                    pagination={false}
                    size="small"
                    bordered
                    rowKey="label"
                    showHeader={false}
                  />
                </Col>

                <Col xs={24} md={14} lg={16}>
                  {selectedPlot.coordinates &&
                  selectedPlot.coordinates.length > 0 ? (
                    <PlotMap plot={selectedPlot} />
                  ) : (
                    <div
                      style={{
                        height: "400px",
                        display: "flex",
                        alignItems: "center",
                        justifyContent: "center",
                        backgroundColor: "#f5f5f5",
                        borderRadius: "8px",
                        border: "1px dashed #d9d9d9",
                      }}>
                      <Text type="secondary">{tConnection("no_map_data")}</Text>
                    </div>
                  )}
                </Col>
              </Row>
            )}
          </AppModal>
        </>
      ) : (
        <>
          <CustomTable
            tableId="land-share-all"
            dataSource={landsShareAll?.data.records}
            columns={columnsLandShareAll}
            rowKey="plot_code"
            pagination={{
              current: paramsShareAll.page,
              pageSize: paramsShareAll.limit,
              total: landsShareAll?.data.total_records,
            }}
            onChange={(pagination) => {
              setParamsShareAll((prev) => ({
                ...prev,
                page: pagination.current ?? prev.page,
                limit: pagination.pageSize ?? prev.limit,
              }));
            }}
            onRow={(record) => ({
              onClick: () => {
                setSelectedSharedPlotCode(record.plot_code);
                setSharedUsersParams({ page: 1, limit: 10 });
              },
              style: { cursor: "pointer" },
            })}
            scroll={{ x: "max-content" }}
          />

          <AppModal
            title={`${tConnection("shared_user_list_title")} - ${tConnection("plot_code")}: ${selectedSharedPlotCode?.toUpperCase()}`}
            open={!!selectedSharedPlotCode}
            onCancel={() => setSelectedSharedPlotCode(null)}
            footer={null}
            width={850}
            centered>
            <CustomTable
              tableId="shared-users-table"
              dataSource={sharedUsersData?.records || []}
              columns={sharedUserColumns}
              rowKey="user_id"
              loading={isFetchingSharedUsers}
              pagination={{
                current: sharedUsersParams.page,
                pageSize: sharedUsersParams.limit,
                total: sharedUsersData?.total_records || 0,
              }}
              onChange={(pagination) => {
                setSharedUsersParams((prev) => ({
                  ...prev,
                  page: pagination.current ?? 1,
                  limit: pagination.pageSize ?? 10,
                }));
              }}
              scroll={{ x: "max-content" }}
            />
          </AppModal>
        </>
      )}
    </Space>
  );
};

export default StaticsShare;
