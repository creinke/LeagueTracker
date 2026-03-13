import React, {useEffect, useState} from 'react';
import {View, Text, StyleSheet, ActivityIndicator, ScrollView} from 'react-native';
import PlayerService from '../../services/PlayerService';
import {PlayerDetail} from '../../types';

export default function PlayerDetailScreen({route}: any) {
    const {id} = route.params;
    const [player, setPlayer] = useState<PlayerDetail | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        PlayerService.getPlayer(id)
            .then(setPlayer)
            .finally(() => setLoading(false));
    }, [id]);

    if (loading) {
        return <ActivityIndicator size="large" style={styles.centered}/>;
    }

    if (!player) {
        return <View style={styles.centered}><Text>Player not found</Text></View>;
    }

    return (
        <ScrollView style={styles.container}>
            <View style={styles.section}>
                <Text style={styles.label}>Name</Text>
                <Text
                    style={styles.value}>{player.firstname} {player.middlenameOrInitial} {player.lastname} {player.generation}</Text>
            </View>
            <View style={styles.section}>
                <Text style={styles.label}>Handicap Index</Text>
                <Text style={styles.value}>{player.seedHandicapIndex}</Text>
            </View>
            <View style={styles.section}>
                <Text style={styles.label}>Contact</Text>
                <Text style={styles.value}>Email: {player.email || 'N/A'}</Text>
                <Text style={styles.value}>Phone: {player.phone || 'N/A'}</Text>
            </View>
            {player.address && (
                <View style={styles.section}>
                    <Text style={styles.label}>Address</Text>
                    <Text style={styles.value}>{player.address.line1}</Text>
                    {player.address.line2 && <Text style={styles.value}>{player.address.line2}</Text>}
                    <Text
                        style={styles.value}>{player.address.city}, {player.address.region} {player.address.postalCode}</Text>
                </View>
            )}
        </ScrollView>
    );
}

const styles = StyleSheet.create({
    container: {flex: 1, backgroundColor: '#fff', padding: 20},
    centered: {flex: 1, justifyContent: 'center', alignItems: 'center'},
    section: {marginBottom: 20},
    label: {fontSize: 14, color: '#666', marginBottom: 5},
    value: {fontSize: 18, fontWeight: '500'},
});
