import { useState, useEffect } from "react";
import { useTranslation } from "react-i18next";
import { Card, CardContent, CardHeader, Box, Typography, LinearProgress, Alert, Tabs, Tab } from "@mui/material";
import Grid from '@mui/material/Grid2';
import { MemData } from '@interfaces/host.types';
import { getMemInfo } from '@services/host';
import { PieChart, Pie, Cell, ResponsiveContainer, BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend } from 'recharts';

export const MemInfo = () => {
    const { t } = useTranslation();
    const [memData, setMemData] = useState<MemData | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);
    const [activeTab, setActiveTab] = useState(0);

    useEffect(() => {
        const fetchMemInfo = async () => {
            try {
                const data = await getMemInfo();
                setMemData(data);
                setError(null);
            } catch (err) {
                setError(err instanceof Error ? err.message : t('host.unknownError'));
                console.error(t('host.memInfoLoadError'), err);
            } finally {
                setLoading(false);
            }
        };

        fetchMemInfo();

        // Aktualisierung alle 5 Sekunden
        const interval = setInterval(fetchMemInfo, 5000);
        return () => clearInterval(interval);
    }, [t]);

    const handleTabChange = (_: React.SyntheticEvent, newValue: number) => {
        setActiveTab(newValue);
    };

    // Formatierte Daten für die Charts
    const getPieChartData = () => {
        if (!memData) return [];
        
        const used = parseFloat(memData.used.replace('G', ''));
        const available = parseFloat(memData.available.replace('G', ''));
        
        return [
            { name: t('host.memory.used'), value: used },
            { name: t('host.memory.available'), value: available }
        ];
    };

    const getBarChartData = () => {
        if (!memData) return [];
        
        return [
            { name: t('host.memory.memory'), 
              [t('host.memory.total')]: parseFloat(memData.total.replace('G', '')), 
              [t('host.memory.used')]: parseFloat(memData.used.replace('G', '')), 
              [t('host.memory.available')]: parseFloat(memData.available.replace('G', '')) }
        ];
    };

    const COLORS = ['#ff4444', '#00c853'];

    // Interface für die Tooltip-Props
    interface TooltipProps {
        active?: boolean;
        payload?: Array<PayloadEntry>;
        label?: string;
    }

    // Interface für die Payload-Einträge
    interface PayloadEntry {
        name?: string;
        value: number;
        dataKey?: string;
        color?: string;
    }

    // Benutzerdefinierter Tooltip mit angepasstem Styling
    const CustomTooltip = ({ active, payload, label }: TooltipProps) => {
        if (active && payload && payload.length) {
            return (
                <div style={{ 
                    backgroundColor: 'rgba(40, 40, 40, 0.9)', 
                    padding: '8px', 
                    border: '1px solid #444',
                    borderRadius: '5px',
                    color: 'white',
                    fontSize: '12px' // Kleinere Schriftgröße
                }}>
                    {/* Prüfe, ob label definiert ist, sonst zeige nichts an */}
                    {label && <p style={{ margin: '2px 0' }}>{`${label}`}</p>}
                    
                    {payload.map((entry: PayloadEntry, index: number) => (
                        <p key={`item-${index}`} style={{ color: entry.color, margin: '2px 0' }}>
                            {/* Zeige den Namen der Payload-Daten an (verwendet/verfügbar) */}
                            {`${entry.name || entry.dataKey}: ${parseFloat(entry.value.toString()).toFixed(2)}G`}
                        </p>
                    ))}
                </div>
            );
        }
        return null;
    };

    return (
        <Box sx={{ flexGrow: 1, p: 2 }}>
            <Card elevation={3} sx={{ borderRadius: 3, minHeight: 400 }}>
                <CardHeader 
                    title={t('host.memory.information')}
                    action={
                        <Tabs value={activeTab} onChange={handleTabChange}>
                            <Tab label={t('host.memory.distribution')} />
                            <Tab label={t('host.memory.usage')} />
                        </Tabs>
                    }
                />
                <CardContent>
                {loading ? (
                        <Box display="flex" justifyContent="center" p={2}>
                            <LinearProgress />
                        </Box>
                    ) : error ? (
                        <Alert severity="error">{error}</Alert>
                    ) : (
                    <Grid container spacing={2}>
                        {activeTab === 0 ? (
                            <Grid size={{ xs: 12 }}>
                                <Box sx={{ 
                                    width: '100%', 
                                    height: 300,
                                    backgroundColor: 'transparent',
                                    '& .recharts-wrapper': {
                                        backgroundColor: 'transparent',
                                    },
                                    '& .recharts-surface': {
                                        backgroundColor: 'transparent',
                                    },
                                    '& .recharts-default-tooltip': {
                                        backgroundColor: 'rgba(40, 40, 40, 0.9) !important',
                                        border: '1px solid #444 !important',
                                    }
                                }}>
                                    <ResponsiveContainer width="100%" height="100%">
                                        <PieChart>
                                            <Pie
                                                data={getPieChartData()}
                                                cx="50%"
                                                cy="50%"
                                                labelLine={false}
                                                outerRadius={120}
                                                fill="#8884d8"
                                                dataKey="value"
                                            >
                                                {getPieChartData().map((_, index) => (
                                                    <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                                                ))}
                                            </Pie>
                                            <Tooltip content={<CustomTooltip />} />
                                            <Legend />
                                        </PieChart>
                                    </ResponsiveContainer>
                                </Box>
                            </Grid>
                        ) : (
                            <Grid size={{ xs: 12 }}>
                                {/* Wrapper mit einem konstanten Hintergrund */}
                                <Box sx={{ 
                                    width: '100%', 
                                    height: 300,
                                    backgroundColor: 'transparent',
                                    '& .recharts-wrapper': {
                                        backgroundColor: 'transparent',
                                    },
                                    '& .recharts-surface': {
                                        backgroundColor: 'transparent',
                                    },
                                    '& .recharts-default-tooltip': {
                                        backgroundColor: 'rgba(40, 40, 40, 0.9) !important',
                                        border: '1px solid #444 !important',
                                    }
                                }}>
                                    <ResponsiveContainer width="100%" height="100%">
                                        <BarChart 
                                            data={getBarChartData()}
                                            style={{ backgroundColor: 'transparent' }}
                                        >
                                            <CartesianGrid strokeDasharray="3 3" stroke="#444" />
                                            <XAxis dataKey="name" />
                                            <YAxis />
                                            <Tooltip 
                                                content={<CustomTooltip />} 
                                                cursor={{ fill: 'rgba(50, 50, 50, 0.3)' }} // Setzt die Farbe beim Hover
                                            />
                                            <Legend />
                                            <Bar dataKey={t('host.memory.total')} fill="#8884d8" />
                                            <Bar dataKey={t('host.memory.used')} fill="#ff4444" />
                                            <Bar dataKey={t('host.memory.available')} fill="#00c853" />
                                        </BarChart>
                                    </ResponsiveContainer>
                                </Box>
                            </Grid>
                        )}
                        <Grid size={{ xs: 12 }}>
                            <Typography variant="body1" sx={{ mt: 2, textAlign: 'center' }}>
                                {t('host.memory.totalMemory')}: {memData?.total || 'N/A'} | 
                                {t('host.memory.used')}: {memData?.used || 'N/A'} | 
                                {t('host.memory.available')}: {memData?.available || 'N/A'}
                            </Typography>
                        </Grid>
                    </Grid>
                    )}
                </CardContent>
            </Card>
        </Box>
    );
};

export default MemInfo;