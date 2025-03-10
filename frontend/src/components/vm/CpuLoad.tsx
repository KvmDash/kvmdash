import { useState, useEffect } from 'react';
import { LinearProgress, Typography, Box, Stack } from '@mui/material';
import type { VmStats } from '../../types/vm.types';

interface CpuLoadProps {
    stats: VmStats;
}

export default function CpuLoad({ stats }: CpuLoadProps) {
    const [lastCpuTime, setLastCpuTime] = useState(stats.stats.cpu_time);
    const [lastUpdate, setLastUpdate] = useState(Date.now());
    const [cpuUsage, setCpuUsage] = useState(0);

    useEffect(() => {
        const now = Date.now();
        const timeDiff = (now - lastUpdate) / 1000; // Zeit in Sekunden
        const cpuTimeDiff = stats.stats.cpu_time - lastCpuTime;
        
        // CPU Usage in Prozent (cpuTimeDiff / timeDiff gibt uns die CPU-Zeit pro Sekunde)
        const usage = (cpuTimeDiff / timeDiff) * 100 / stats.cpuCount;
        
        setCpuUsage(Math.min(Math.max(usage, 0), 100));
        setLastCpuTime(stats.stats.cpu_time);
        setLastUpdate(now);
    }, [stats]);

    return (    
        <Stack spacing={2}>
            <Typography variant="body2" color="text.secondary">
                CPUs: {stats.cpuCount}
            </Typography>
            
            <Box>
                <Typography variant="body2" color="text.secondary" gutterBottom>
                    CPU Auslastung: {cpuUsage.toFixed(1)}%
                </Typography>
                <LinearProgress 
                    variant="determinate" 
                    value={cpuUsage} 
                    sx={{ height: 10, borderRadius: 5 }}
                />
            </Box>
            
            <Typography variant="body2" color="text.secondary">
                CPU Zeit: {stats.stats.cpu_time.toFixed(2)}s
            </Typography>
        </Stack>
    );
}