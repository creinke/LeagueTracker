import React, {useEffect, useState} from 'react';
import {View, Text, FlatList, TouchableOpacity, StyleSheet, ActivityIndicator} from 'react-native';
import PlayerService from '../../services/PlayerService';
import {Player} from '../../types';

export default function PlayerListScreen({navigation}: any) {
    const [players, setPlayers] = useState<Player[]>([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        PlayerService.getPlayers()
            .then(setPlayers)
            .finally(() => setLoading(false));
    }, []);

    if (loading) {
        return <ActivityIndicator size="large" style={styles.centered}/>;
    }

    return (
        <View style={styles.container}>
            <FlatList
                data={players}
                keyExtractor={(item) => item.id.toString()}
                renderItem={({item}) => (
                    <TouchableOpacity
                        style={styles.item}
                        onPress={() => navigation.navigate('PlayerDetail', {id: item.id})}
                    >
                        <Text style={styles.name}>{item.firstname} {item.lastname}</Text>
                        <Text>Handicap Index: {item.seedHandicapIndex}</Text>
                    </TouchableOpacity>
                )}
            />
        </View>
    );
}

const styles = StyleSheet.create({
    container: {flex: 1, backgroundColor: '#fff'},
    centered: {flex: 1, justifyContent: 'center', alignItems: 'center'},
    item: {padding: 15, borderBottomWidth: 1, borderBottomColor: '#eee'},
    name: {fontSize: 18, fontWeight: 'bold'},
});
